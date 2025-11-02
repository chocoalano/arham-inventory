<!DOCTYPE html>
<html class="no-js" lang="zxx">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	{{-- Blade directive untuk judul spesifik halaman. Defaultnya 'Furniture eCommerce Template'. --}}
	<title>Pataku - @yield('title', 'Furniture eCommerce Template')</title>
	<meta name="url" content="{{ config('app.url') }}">
	<meta name="asset" content="{{ config('app.asset_url') }}">
	<meta name="description" content="@yield('meta_description', '')">
	<meta name="keywords" content="@yield('meta_keywords', '')">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- Favicon -->
	<link rel="icon" href="ecommerce/images/favicon.ico">

	<!-- Google Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;1,100;1,300;0,400;0,700&family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
    @livewireStyles
    @vite(['resources/css/app.css','resources/js/app.js'])
	<!-- CSS
	============================================ -->
	<!-- Bootstrap CSS -->
	<link href="{{ asset('ecommerce/css/bootstrap.min.css') }}" rel="stylesheet">

	<!-- FontAwesome CSS -->
	<link href="{{ asset('ecommerce/css/font-awesome.min.css') }}" rel="stylesheet">

	<!-- Linear Icon CSS -->
	<link href="{{ asset('ecommerce/css/linear-icon.css') }}" rel="stylesheet">

	<!-- Plugins CSS -->
	<link href="{{ asset('ecommerce/css/plugins.css') }}" rel="stylesheet">

	<!-- Helper CSS -->
	<link href="{{ asset('ecommerce/css/helper.css') }}" rel="stylesheet">

	<!-- Main CSS -->
	<link href="{{ asset('ecommerce/css/main.css') }}" rel="stylesheet">

	{{-- Blade stack untuk CSS tambahan di halaman spesifik --}}
	@stack('css')

	<!-- Modernizer JS -->
	<script src="{{ asset('ecommerce/js/vendor/modernizr-2.8.3.min.js') }}"></script>

</head>

<body>

	<!--=============================================
	=             Header One         =
	=============================================-->

	@livewire('ecommerce.layouts.header-components')

	<!--=====  End of Header One  ======-->

	{{-- Bagian ini akan diisi oleh konten spesifik dari setiap view. --}}
	<main class="main-content-wrapper">
		@yield('content')
	</main>

	<!--=============================================
	=             JS FOOTER SCRIPTS          =
	=============================================-->
    @livewireScripts
    @include('ecommerce.layouts.partials.footer')

	{{-- Blade stack untuk JavaScript tambahan di halaman spesifik --}}
    <!-- JS ============================================ -->
	<!-- jQuery JS -->
	<script src="{{ asset('ecommerce/js/vendor/jquery.min.js') }}"></script>

	<!-- Popper JS -->
	<script src="{{ asset('ecommerce/js/popper.min.js') }}"></script>

	<!-- Bootstrap JS -->
	<script src="{{ asset('ecommerce/js/bootstrap.min.js') }}"></script>

	<!-- Plugins JS -->
	<script src="{{ asset('ecommerce/js/plugins.js') }}"></script>

	<!-- Main JS -->
	<script src="{{ asset('ecommerce/js/main.js') }}"></script>
	@stack('js')

</body>

</html>
