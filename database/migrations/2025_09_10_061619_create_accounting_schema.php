<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ===== 1. Fiscal Years =====
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('year')->unique()->comment('Tahun fiskal, misal 2025');
            $table->date('starts_on')->comment('Mulai tahun fiskal');
            $table->date('ends_on')->comment('Akhir tahun fiskal');
            $table->boolean('is_closed')->default(false)->comment('Ditutup untuk pembukuan?');
            $table->timestamps();
            $table->softDeletes();
        });

        // ===== 2. Periods (bulan/kuartal) =====
        Schema::create('periods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('period_no')->comment('1..12 (atau 13 untuk adjustment)');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['fiscal_year_id', 'period_no']);
            $table->index(['starts_on', 'ends_on']);
        });

        // ===== 3. Cost Centers (opsional) =====
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // ===== 4. Accounts (Chart of Accounts) =====
        Schema::create('accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('number', 32)->unique()->comment('Nomor akun, misal 1101');
            $table->string('name', 150);
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense'])->comment('Tipe utama laporan');
            $table->string('subtype', 64)->nullable()->comment('Sub-kategori opsional, misal cash, ar, inventory');
            $table->boolean('is_postable')->default(true)->comment('Bisa diposting langsung?');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
        });

        // ===== 5. Account default mapping (opsional, untuk integrasi otomatis) =====
        Schema::create('account_mappings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 64)->unique()->comment('Kunci mapping, misal sales_revenue, cogs, inventory, ar, ap, tax_output, tax_input, shipping_income');
            $table->foreignId('account_id')->constrained('accounts');
            $table->timestamps();
        });

        // ===== 6. Journals (header) =====
        Schema::create('journals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('journal_no', 64)->unique()->comment('Nomor jurnal (unik)');
            $table->date('journal_date');
            $table->foreignId('period_id')->nullable()->constrained('periods')->nullOnDelete();
            // Polymorphic link to source document
            $table->string('source_type', 64)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->enum('status', ['draft', 'posted', 'void'])->default('draft');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['journal_date', 'status']);
            $table->index(['source_type', 'source_id']);
        });

        // ===== 7. Journal Lines (detail) =====
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->string('description', 255)->nullable();
            $table->decimal('debit', 20, 2)->default(0);
            $table->decimal('credit', 20, 2)->default(0);
            $table->string('currency', 3)->nullable()->comment('Kode mata uang, default null=IDR');
            $table->decimal('fx_rate', 20, 8)->nullable()->comment('Kurs ke IDR bila currency diisi');
            $table->timestamps();

            $table->index(['account_id']);
        });

        // ===== 8. Optional: link barang/varian -> akun (untuk COGS/Inventory per produk) =====
        Schema::create('product_account_links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('sales_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();

            $table->unique(['product_id']);
        });

        // ===== 9. SQL Views untuk laporan cepat =====

        // General Ledger (buku besar)
        DB::statement("
            CREATE OR REPLACE VIEW v_general_ledger AS
            SELECT
                jl.id AS id,
                jl.id AS line_id,
                jl.description as jl_description,
                j.journal_no,
                j.journal_date,
                j.status,
                j.period_id,
                a.number AS account_number,
                a.name   AS account_name,
                a.type   AS account_type,
                cc.code   AS cost_center_code,
                cc.name   AS cost_center_name,
                jl.description,
                jl.debit,
                jl.credit,
                (jl.debit - jl.credit) AS dc,
                j.source_type,
                j.source_id
            FROM journal_lines jl
            JOIN journals j ON j.id = jl.journal_id
            JOIN accounts a ON a.id = jl.account_id
            LEFT JOIN cost_centers cc ON cc.id = jl.cost_center_id
            WHERE j.deleted_at IS NULL;
        ");

        // -- Trial Balance (saldo per akun per periode)
        DB::statement("
            CREATE OR REPLACE VIEW v_trial_balance AS
            SELECT
                -- jadikan kombinasi period_id + account_id sebagai id sintetis yang stabil
                CONCAT_WS('-', COALESCE(p.id, 0), a.id) AS id,
                p.id AS period_id,
                p.period_no,
                fy.year AS fiscal_year,
                a.id AS account_id,
                a.number AS account_number,
                a.name AS account_name,
                a.type AS account_type,
                SUM(jl.debit)  AS total_debit,
                SUM(jl.credit) AS total_credit,
                SUM(jl.debit - jl.credit) AS balance
            FROM journal_lines jl
            JOIN journals j ON j.id = jl.journal_id AND j.status = 'posted'
            JOIN accounts a ON a.id = jl.account_id
            LEFT JOIN periods p ON p.id = j.period_id
            LEFT JOIN fiscal_years fy ON fy.id = p.fiscal_year_id
            WHERE j.deleted_at IS NULL
            GROUP BY
                id,               -- penting: group by kolom yang kita select
                p.id, p.period_no, fy.year,
                a.id, a.number, a.name, a.type
        ");

        // -- Profit & Loss (ringkas)
        DB::statement("
            CREATE OR REPLACE VIEW view_profit_and_losses AS
            SELECT
            CONCAT('pl-', COALESCE(p.id, 0))                               AS id,
            p.id                                                           AS period_id,
            p.period_no                                                    AS period_no,
            p.starts_on                                                    AS starts_on,
            p.ends_on                                                      AS ends_on,
            fy.year                                                        AS fiscal_year,
            ROUND(SUM(CASE WHEN a.type = 'revenue' THEN (jl.credit - jl.debit) ELSE 0 END), 2) AS total_revenue,
            ROUND(SUM(CASE WHEN a.type = 'expense' THEN (jl.debit - jl.credit) ELSE 0 END), 2) AS total_expense,
            ROUND(SUM(CASE
                WHEN a.type = 'revenue' THEN (jl.credit - jl.debit)
                WHEN a.type = 'expense' THEN (jl.debit - jl.credit)
                ELSE 0
            END), 2) AS net_profit
            FROM journals j
            JOIN journal_lines jl ON jl.journal_id = j.id
            JOIN accounts a       ON a.id = jl.account_id
            LEFT JOIN periods p        ON p.id = j.period_id
            LEFT JOIN fiscal_years fy  ON fy.id = p.fiscal_year_id
            WHERE j.status = 'posted'
            AND j.deleted_at IS NULL
            AND (jl.deleted_at IS NULL OR jl.deleted_at = '0000-00-00') -- sesuaikan jika tidak pakai soft delete di jl
            GROUP BY
            p.id, p.period_no, p.starts_on, p.ends_on, fy.year;
        ");


        // -- Balance Sheet (ringkas)
        DB::statement("
            CREATE OR REPLACE VIEW v_balance_sheet AS
            SELECT
                CONCAT('bs-', COALESCE(p.id, 0)) AS id,  -- id string untuk Neraca
                p.id AS period_id,
                p.period_no,
                p.starts_on,
                p.ends_on,
                fy.year AS fiscal_year,
                SUM(CASE WHEN a.type='asset'     THEN (jl.debit - jl.credit) ELSE 0 END) AS total_assets,
                SUM(CASE WHEN a.type='liability' THEN (jl.credit - jl.debit) ELSE 0 END) AS total_liabilities,
                SUM(CASE WHEN a.type='equity'    THEN (jl.credit - jl.debit) ELSE 0 END) AS total_equity,
                SUM(CASE
                        WHEN a.type='asset'                 THEN (jl.debit - jl.credit)
                        WHEN a.type IN ('liability','equity') THEN (jl.credit - jl.debit)
                        ELSE 0
                    END) AS accounting_equation
            FROM journal_lines jl
            JOIN journals j ON j.id = jl.journal_id AND j.status = 'posted'
            JOIN accounts a ON a.id = jl.account_id
            LEFT JOIN periods p ON p.id = j.period_id
            LEFT JOIN fiscal_years fy ON fy.id = p.fiscal_year_id
            WHERE j.deleted_at IS NULL
            GROUP BY id, p.id, p.period_no, fy.year;
        ");


        // ===== 10. Indeks bantu rekomendasi performa =====
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->index(['journal_id', 'account_id']);
        });
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_balance_sheet");
        DB::statement("DROP VIEW IF EXISTS v_profit_and_loss");
        DB::statement("DROP VIEW IF EXISTS v_trial_balance");
        DB::statement("DROP VIEW IF EXISTS v_general_ledger");

        Schema::dropIfExists('product_account_links');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('account_mappings');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('periods');
        Schema::dropIfExists('fiscal_years');
    }
};
