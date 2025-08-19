<?php
namespace App\Models\Traits;

trait PaymentProcessor
{
    /**
     * Memformat jumlah pembayaran ke dalam mata uang yang mudah dibaca.
     *
     * @return string
     */
    public function formatAmount(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }
}
