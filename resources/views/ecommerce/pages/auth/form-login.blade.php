<form action="{{ route('login.submit') }}" method="POST">
    @csrf
    <div class="login-form">
        <h4 class="login-title">Masuk (Login)</h4>

        {{-- Menampilkan pesan status (misalnya dari redirect with success) --}}
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        {{-- Menampilkan semua error validasi secara umum --}}
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
            {{-- Kolom Email --}}
            <div class="col-md-12 col-12 mb-20">
                <label for="email">Alamat Email*</label>
                <input id="email" name="email" class="mb-0" type="email" placeholder="Masukkan Alamat Email Anda"
                    value="{{ old('email') }}" required autofocus autocomplete="email" {{-- Tambahkan untuk
                    auto-complete browser --}}>
                {{-- Menampilkan error spesifik untuk field 'email' --}}
                @error('email')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            {{-- Kolom Kata Sandi (Password) --}}
            <div class="col-12 mb-20">
                <label for="password">Kata Sandi*</label>
                <input id="password" name="password" class="mb-0" type="password" placeholder="Masukkan Kata Sandi"
                    required autocomplete="current-password" {{-- Tambahkan untuk auto-complete browser --}}>
                {{-- Menampilkan error spesifik untuk field 'password' --}}
                @error('password')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-8">
                {{-- Checkbox Ingat Saya --}}
                <div class="check-box d-inline-block ml-0 ml-md-2 mt-10">
                    <input type="checkbox" id="remember_me" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember_me">Ingat Saya</label>
                </div>
            </div>

            <div class="col-md-4 mt-10 mb-20 text-start text-md-end">
                {{-- Link Lupa Kata Sandi --}}
                <a href="#">Lupa kata sandi?</a>
            </div>

            {{-- Tombol Submit --}}
            <div class="col-md-12">
                <button type="submit" class="register-button mt-0">Masuk</button>
            </div>
        </div>
    </div>
</form>
