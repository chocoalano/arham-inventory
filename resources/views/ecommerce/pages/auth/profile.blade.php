@extends('ecommerce.layouts.app')
@section('title', 'Profil Saya')
@section('content')
    @include('ecommerce.layouts.partials.breadscrum')
    <div class="page-section mb-80">
        <div class="container">
            {{-- Flash alerts --}}
            @foreach (['success', 'error', 'warning', 'info', 'status'] as $key)
                @if (session($key))
                    @php
                        $map = [
                            'success' => 'success',
                            'error' => 'danger',
                            'warning' => 'warning',
                            'info' => 'info',
                            'status' => 'success',
                        ];
                        $class = $map[$key] ?? 'info';
                    @endphp
                    <div class="alert alert-{{ $class }} alert-dismissible fade show" role="alert">
                        {{ session($key) }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            @endforeach

            {{-- Validasi error --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Terjadi kesalahan.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="row">
                        <!-- My Account Tab Menu Start -->
                        <div class="col-lg-3 col-12">
                            <div class="myaccount-tab-menu nav" role="tablist">
                                <a href="#dashboad" class="active" data-bs-toggle="tab" aria-selected="true" role="tab"><i
                                        class="fa fa-dashboard"></i>
                                    Dasbor</a>
                                <a href="#orders" data-bs-toggle="tab" aria-selected="false" tabindex="-1" role="tab"><i
                                        class="fa fa-cart-arrow-down"></i> Pesanan</a>
                                <a href="#account-info" data-bs-toggle="tab" aria-selected="false" tabindex="-1"
                                    role="tab"><i class="fa fa-user"></i> Detail Akun</a>
                                {{-- <a href="login-register.html"><i class="fa fa-sign-out"></i> Keluar</a> --}}
                                <form method="POST" action="{{ route('auth.logout') }}" style="display:inline;">
                                    @csrf
                                    <a type="submit"><i class="fa fa-sign-out"></i> Keluar</a>
                                </form>
                            </div>
                        </div>
                        <!-- My Account Tab Menu End -->

                        <!-- My Account Tab Content Start -->
                        <div class="col-lg-9 col-12">
                            <div class="tab-content" id="myaccountContent">
                                <!-- Single Tab Content Start -->
                                <div class="tab-pane fade active show" id="dashboad" role="tabpanel">
                                    <div class="myaccount-content card p-4">
                                        <h4 class="card-title mb-4">Dasbor Akun</h4>

                                        <div class="welcome mb-4">
                                            <p class="mb-0">
                                                Selamat datang kembali,
                                                <strong>{{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) }}</strong>
                                                <span class="text-muted">(&nbsp;Bukan Anda?)</span>
                                            </p>

                                            <form method="POST" action="{{ route('auth.logout') }}"
                                                class="d-inline-block mt-2">
                                                @csrf
                                                <button type="submit" class="btn btn-link p-0 text-danger">Keluar</button>
                                            </form>
                                        </div>

                                        {{-- Ringkasan Status --}}
                                        <div class="row mb-4 text-center">
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="card bg-light p-3 h-100 border-primary shadow-sm">
                                                    <h5 class="text-primary">Total Pesanan</h5>
                                                    <h2 class="display-4 fw-bold">
                                                        {{ $user->dashboardOrderStats()['total_orders'] ?? 0 }}
                                                    </h2>
                                                    <p class="mb-0 text-muted">Sepanjang waktu</p>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="card bg-light p-3 h-100 border-success shadow-sm">
                                                    <h5 class="text-success">Pesanan Sudah terbayar</h5>
                                                    <h2 class="display-4 fw-bold">
                                                        {{ $user->dashboardOrderStats()['total_paid_orders'] ?? 0 }}
                                                    </h2>
                                                    <p class="mb-0 text-muted">Pesanan sudah terbayar</p>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="card bg-light p-3 h-100 border-warning shadow-sm">
                                                    <h5 class="text-warning">Pesanan Belum terbayar</h5>
                                                    <h2 class="display-4 fw-bold">
                                                        {{ $user->dashboardOrderStats()['total_unpaid_orders'] ?? 0 }}
                                                    </h2>
                                                    <p class="mb-0 text-muted">Pesanan belum terbayar</p>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="card bg-light p-3 h-100 border-danger shadow-sm">
                                                    <h5 class="text-danger">Pesanan Dibatalkan</h5>
                                                    <h2 class="display-4 fw-bold">
                                                        {{ $user->dashboardOrderStats()['total_cancelled_orders'] ?? 0 }}
                                                    </h2>
                                                    <p class="mb-0 text-muted">Pesanan dibatalkan</p>
                                                </div>
                                            </div>
                                        </div>

                                        <h5 class="mt-2">Informasi Akun</h5>
                                        <p class="mb-0">Dari dashboard ini, Anda dapat:</p>
                                        <ul class="list-unstyled">
                                            <li><i class="fa fa-check text-success me-2"></i> Memeriksa &amp; melihat
                                                pesanan terakhir Anda.</li>
                                            <li><i class="fa fa-check text-success me-2"></i> Mengelola alamat pengiriman
                                                dan penagihan.</li>
                                            <li><i class="fa fa-check text-success me-2"></i> Mengubah kata sandi dan detail
                                                akun Anda.</li>
                                        </ul>
                                    </div>
                                </div>
                                <!-- Single Tab Content End -->

                                <!-- Single Tab Content Start -->
                                <div class="tab-pane fade" id="orders" role="tabpanel">
                                    <div class="myaccount-content">
                                        <h3>Pesanan</h3>

                                        <div class="myaccount-table table-responsive text-center">
                                            <table class="table table-bordered">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>No. Transaksi</th>
                                                        <th>Tanggal Pesanan</th>
                                                        <th>Jumlah Barang</th>
                                                        <th>Total Pembayaran</th>
                                                        <th>Status</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    @foreach ($orders as $k => $v)
                                                        @php
                                                            $badgeClass = match ($v->status) {
                                                                'paid', 'settlement' => 'bg-success',
                                                                'pending', 'draft' => 'bg-warning text-dark',
                                                                'cancelled', 'expired', 'deny' => 'bg-danger',
                                                                default => 'bg-secondary',
                                                            };
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $v->reference_number }}</td>
                                                            <td>{{ $v->created_at->format('d M Y') }}</td>
                                                            <td>{{ $v->item_count }}</td>
                                                            <td>Rp{{ number_format($v->grand_total, 0, ',', '.') }}</td>
                                                            <td><span
                                                                    class="badge {{ $badgeClass }}">{{ ucfirst($v->status) }}</span>
                                                            </td>
                                                            <td><a href="{{ route('orders.show', $v->reference_number) }}"
                                                                    class="btn">Lihat</a></td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            @if($orders->hasPages())
                                                <div class="mt-4">
                                                    {{ $orders->links('pagination::bootstrap-5') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <!-- Single Tab Content End -->

                                <!-- Single Tab Content Start -->
                                <div class="tab-pane fade" id="account-info" role="tabpanel">
                                    <div class="myaccount-content">
                                        <h3>Account Details</h3>

                                        <div class="account-details-form">
                                            <form action="{{ route('auth.profile.update') }}" method="POST" novalidate>
                                                @csrf

                                                <div class="row">
                                                    {{-- Nama Depan --}}
                                                    <div class="col-lg-6 col-12 mb-30">
                                                        <label for="first-name" class="form-label">Nama Depan</label>
                                                        <input id="first-name" name="first_name" placeholder="Nama Depan"
                                                            type="text"
                                                            value="{{ old('first_name', $user->first_name ?? '') }}"
                                                            class="@error('first_name') is-invalid @enderror"
                                                            autocomplete="given-name" required>
                                                        @error('first_name') <small
                                                        class="invalid-feedback">{{ $message }}</small> @enderror
                                                    </div>

                                                    {{-- Nama Belakang --}}
                                                    <div class="col-lg-6 col-12 mb-30">
                                                        <label for="last-name" class="form-label">Nama Belakang</label>
                                                        <input id="last-name" name="last_name" placeholder="Nama Belakang"
                                                            type="text"
                                                            value="{{ old('last_name', $user->last_name ?? '') }}"
                                                            class="@error('last_name') is-invalid @enderror"
                                                            autocomplete="family-name">
                                                        @error('last_name') <small
                                                        class="invalid-feedback">{{ $message }}</small> @enderror
                                                    </div>

                                                    {{-- Nama Lengkap (readonly, gabungan nama depan+belakang) --}}
                                                    <div class="col-12 mb-30">
                                                        <label for="display-name" class="form-label">Nama Lengkap</label>
                                                        <input id="display-name" placeholder="Nama Lengkap" type="text"
                                                            value="{{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) }}"
                                                            readonly>
                                                        <small class="text-muted d-block mt-1">Diambil dari Nama Depan/Nama
                                                            Belakang (tidak disimpan ke database).</small>
                                                    </div>

                                                    {{-- Alamat Email --}}
                                                    <div class="col-12 mb-30">
                                                        <label for="email" class="form-label">Alamat Email</label>
                                                        <input id="email" name="email" placeholder="Alamat Email"
                                                            type="email" value="{{ old('email', $user->email ?? '') }}"
                                                            class="@error('email') is-invalid @enderror"
                                                            autocomplete="email" required>
                                                        @error('email') <small
                                                        class="invalid-feedback">{{ $message }}</small> @enderror
                                                    </div>

                                                    {{-- Nomor Telepon --}}
                                                    <div class="col-12 mb-30">
                                                        <label for="phone" class="form-label">Nomor Telepon</label>
                                                        <input id="phone" name="phone" placeholder="Nomor Telepon"
                                                            type="tel" value="{{ old('phone', $user->phone ?? '') }}"
                                                            class="@error('phone') is-invalid @enderror" autocomplete="tel"
                                                            required>
                                                        @error('phone') <small
                                                        class="invalid-feedback">{{ $message }}</small> @enderror
                                                    </div>

                                                    {{-- Perusahaan --}}
                                                    <div class="col-lg-6 col-12 mb-30">
                                                        <label for="company" class="form-label">Perusahaan
                                                            (opsional)</label>
                                                        <input id="company" name="company"
                                                            placeholder="Perusahaan (opsional)" type="text"
                                                            value="{{ old('company', $user->company ?? '') }}"
                                                            class="@error('company') is-invalid @enderror"
                                                            autocomplete="organization">
                                                        @error('company') <small
                                                        class="invalid-feedback">{{ $message }}</small> @enderror
                                                    </div>

                                                    {{-- NPWP / VAT --}}
                                                    <div class="col-lg-6 col-12 mb-30">
                                                        <label for="vat_number" class="form-label">NPWP / VAT
                                                            (opsional)</label>
                                                        <input id="vat_number" name="vat_number"
                                                            placeholder="NPWP / VAT (opsional)" type="text"
                                                            value="{{ old('vat_number', $user->vat_number ?? '') }}"
                                                            class="@error('vat_number') is-invalid @enderror">
                                                        @error('vat_number') <small
                                                        class="invalid-feedback">{{ $message }}</small> @enderror
                                                    </div>

                                                    {{-- Ubah Kata Sandi --}}
                                                    <div class="col-12 mb-10">
                                                        <h4 class="mb-0">Ubah Kata Sandi</h4>
                                                    </div>

                                                    <div class="col-12 mb-30">
                                                        <label for="current-pwd" class="form-label">Kata Sandi Saat
                                                            Ini</label>
                                                        <input id="current-pwd" name="current_password"
                                                            placeholder="Kata Sandi Saat Ini" type="password"
                                                            class="@error('current_password') is-invalid @enderror"
                                                            autocomplete="current-password">
                                                        @error('current_password') <small
                                                        class="invalid-feedback">{{ $message }}</small> @enderror
                                                    </div>

                                                    <div class="col-lg-6 col-12 mb-30">
                                                        <label for="new-pwd" class="form-label">Kata Sandi Baru</label>
                                                        <input id="new-pwd" name="password" placeholder="Kata Sandi Baru"
                                                            type="password" class="@error('password') is-invalid @enderror"
                                                            autocomplete="new-password">
                                                        @error('password') <small
                                                        class="invalid-feedback">{{ $message }}</small> @enderror
                                                    </div>

                                                    <div class="col-lg-6 col-12 mb-30">
                                                        <label for="confirm-pwd" class="form-label">Konfirmasi Kata
                                                            Sandi</label>
                                                        <input id="confirm-pwd" name="password_confirmation"
                                                            placeholder="Konfirmasi Kata Sandi" type="password"
                                                            autocomplete="new-password">
                                                    </div>

                                                    <div class="col-12">
                                                        <button class="save-change-btn" type="submit">Simpan
                                                            Perubahan</button>
                                                    </div>
                                                </div>
                                            </form>

                                        </div>
                                    </div>
                                </div>
                                <!-- Single Tab Content End -->
                            </div>
                        </div>
                        <!-- My Account Tab Content End -->
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
