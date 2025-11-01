@extends('ecommerce.layouts.app')
@section('title', 'Beranda')
@push('css')

@endpush
@section('content')
  @include('ecommerce.layouts.partials.breadscrum')

  <div class="container py-4">
    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger">
        <strong>Terjadi kesalahan:</strong>
        <ul class="mb-0">
          @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @php
      // Ambil user dari controller jika dipass, jika tidak fallback ke guard 'customer' lalu default guard
      $user = $user
        ?? (Auth::guard('customer')->user() ?: Auth::user());

      // Roles: dukung beberapa kemungkinan implementasi
      $roleNames = collect();
      if ($user) {
        if (method_exists($user, 'getRoleNames')) {
          // jika pakai spatie/laravel-permission
          $roleNames = collect($user->getRoleNames())->filter();
        } elseif (isset($user->roles) && method_exists($user->roles, 'pluck')) {
          // relasi roles konvensional
          $roleNames = $user->roles->pluck('name')->filter();
        }
      }

      // Warehouse (relasi belongsTo)
      $warehouseName = optional($user?->warehouse)->name ?? '-';

      // Activity/log: pakai relasi $user->log() jika ada
      $logItems = collect();
      if ($user && method_exists($user, 'log')) {
        $logItems = $user->log()->latest()->limit(10)->get();
      }
    @endphp

    @if ($user)
      <div class="row g-3">
        {{-- Kartu identitas user --}}
        <div class="col-lg-4">
          <div class="card h-100">
            <div class="card-body">
              <h5 class="card-title mb-3">Akun Saya</h5>

              <div class="d-flex align-items-start gap-3">
                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center"
                     style="width:56px;height:56px;">
                  <span class="fw-bold">{{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}</span>
                </div>
                <div>
                  <div class="fw-semibold">{{ $user->name ?? '-' }}</div>
                  <div class="muted">{{ $user->email ?? '-' }}</div>
                  @if(!empty($user->email_verified_at))
                    <div class="text-success small">Email terverifikasi</div>
                  @endif
                </div>
              </div>

              <hr>

              <div class="mb-2">
                <div class="muted small mb-1">Peran</div>
                @if($roleNames->isNotEmpty())
                  <div class="d-flex flex-wrap gap-2">
                    @foreach ($roleNames as $r)
                      <span class="badge">{{ $r }}</span>
                    @endforeach
                  </div>
                @else
                  <div class="muted">—</div>
                @endif
              </div>

              <div class="mt-3">
                <div class="muted small mb-1">Gudang</div>
                <div>{{ $warehouseName }}</div>
              </div>
            </div>
          </div>
        </div>

        {{-- Form update profil --}}
        <div class="col-lg-8">
          <div class="card mb-3">
            <div class="card-body">
              <h5 class="card-title mb-3">Perbarui Profil</h5>

              <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $user->name) }}" required maxlength="100">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email', $user->email) }}" required maxlength="150">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">No. HP</label>
                    <input type="text" name="phone" class="form-control"
                           value="{{ old('phone', $user->phone ?? '') }}" required maxlength="30">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Gudang</label>
                    <input type="text" class="form-control" value="{{ $warehouseName }}" disabled>
                  </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                  <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                  </button>
                  <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">
                    Lihat Profil
                  </a>
                </div>
              </form>
            </div>
          </div>

          {{-- Riwayat aktivitas (opsional, jika relasi log() tersedia) --}}
          <div class="card">
            <div class="card-body">
              <h5 class="card-title mb-3">Aktivitas Terakhir</h5>

              @if ($logItems->isEmpty())
                <div class="muted">Belum ada aktivitas.</div>
              @else
                <div class="table-responsive">
                  <table class="table table-sm align-middle">
                    <thead>
                      <tr>
                        <th style="width: 160px;">Waktu</th>
                        <th>Deskripsi</th>
                        <th style="width: 180px;">Terkait</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($logItems as $log)
                        <tr>
                          <td class="muted">{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('Y-m-d H:i:s') }}</td>
                          <td>{{ $log->description ?? '-' }}</td>
                          <td class="muted">
                            {{-- Sesuaikan properti sesuai model Log kamu --}}
                            {{ $log->subject_type ?? '-' }}
                            @if(!empty($log->subject_id)) #{{ $log->subject_id }} @endif
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    @else
      {{-- <div class="alert alert-warning">
        Anda belum login. <a href="{{ route('user.login') }}">Masuk</a>
      </div> --}}
    @endif
  </div>
@endsection

@push('js')
<script>
  // contoh kecil: auto-hide alert sukses
  document.addEventListener('DOMContentLoaded', () => {
    const el = document.querySelector('.alert.alert-success');
    if (!el) return;
    setTimeout(() => { el.style.display = 'none'; }, 4000);
  });
</script>
@endpush
