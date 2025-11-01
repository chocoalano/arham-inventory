<form action="{{ route('register.submit') }}" method="POST">
    @csrf
    <div class="login-form">
        <h4 class="login-title">Daftar Akun Baru</h4>

        {{-- Menampilkan semua error validasi --}}
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            {{-- Nama Depan --}}
            <div class="col-md-6 col-12 mb-20">
                <label for="first_name">Nama Depan</label>
                <input
                    id="first_name"
                    name="first_name"
                    class="mb-0"
                    type="text"
                    placeholder="Masukkan Nama Depan"
                    value="{{ old('first_name') }}"
                    autocomplete="given-name"
                >
                <small class="form-text text-muted">Contoh: Budi. Tidak wajib diisi.</small>
                @error('first_name')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            {{-- Nama Belakang --}}
            <div class="col-md-6 col-12 mb-20">
                <label for="last_name">Nama Belakang</label>
                <input
                    id="last_name"
                    name="last_name"
                    class="mb-0"
                    type="text"
                    placeholder="Masukkan Nama Belakang"
                    value="{{ old('last_name') }}"
                    autocomplete="family-name"
                >
                <small class="form-text text-muted">Contoh: Santoso. Tidak wajib diisi.</small>
                @error('last_name')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            {{-- Alamat Email --}}
            <div class="col-md-12 mb-20">
                <label for="email">Alamat Email*</label>
                <input
                    id="email"
                    name="email"
                    class="mb-0"
                    type="email"
                    placeholder="Masukkan Alamat Email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                >
                <small class="form-text text-muted">Gunakan alamat email yang aktif dan valid.</small>
                @error('email')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            {{-- Kata Sandi (Password) --}}
            <div class="col-md-6 mb-20">
                <label for="password">Kata Sandi*</label>
                <input
                    id="password"
                    name="password"
                    class="mb-0"
                    type="password"
                    placeholder="Masukkan Kata Sandi"
                    required
                    autocomplete="new-password"
                >
                <small class="form-text text-muted">Minimal 8 karakter. Kombinasi huruf besar, kecil, dan angka disarankan.</small>
                @error('password')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            {{-- Konfirmasi Kata Sandi (Confirm Password) --}}
            <div class="col-md-6 mb-20">
                <label for="password_confirmation">Konfirmasi Kata Sandi*</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    class="mb-0"
                    type="password"
                    placeholder="Ulangi Kata Sandi"
                    required
                    autocomplete="new-password"
                >
                <small class="form-text text-muted">Pastikan kata sandi yang dimasukkan sama dengan di atas.</small>
            </div>

            {{-- Nomor Telepon (Phone) --}}
            <div class="col-md-12 mb-20">
                <label for="phone">Nomor Telepon (Opsional)</label>
                <input
                    id="phone"
                    name="phone"
                    class="mb-0"
                    type="text"
                    placeholder="Masukkan Nomor Telepon"
                    value="{{ old('phone') }}"
                    autocomplete="tel"
                >
                <small class="form-text text-muted">Akan digunakan untuk informasi pengiriman atau notifikasi penting.</small>
                @error('phone')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            {{-- Tombol Submit --}}
            <div class="col-12">
                <button type="submit" class="register-button mt-0">Daftar</button>
            </div>
        </div>
    </div>
</form>
