@extends('ecommerce.layouts.app')
@section('title', 'Beranda')
@push('css')

@endpush
@section('content')
    @include('ecommerce.layouts.partials.breadscrum')
    <div class="page-section mb-80">
        <div class="container">
            <div class="row">
                <div class="col-sm-12 col-md-12 col-xs-12 col-lg-6 mb-30">
                    @include('ecommerce.pages.auth.form-login')
                </div>
                <div class="col-sm-12 col-md-12 col-lg-6 col-xs-12">
                    @include('ecommerce.pages.auth.form-register')
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')

@endpush
