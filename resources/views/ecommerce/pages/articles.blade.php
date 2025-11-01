@extends('ecommerce.layouts.app')
@section('title', 'Beranda')
@push('css')

@endpush
@section('content')
@include('ecommerce.layouts.partials.breadscrum')
<div class="container">
    <h1>Selamat datang di artikel</h1>
    <p>Ini adalah halaman beranda untuk toko online Pataku.</p>
</div>
@endsection
@push('js')

@endpush
