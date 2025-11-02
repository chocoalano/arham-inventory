@extends('ecommerce.layouts.app')
@section('title', 'Beranda')

@push('css')
@endpush

@section('content')
    @include('ecommerce.layouts.partials.breadscrum')

    <div class="container">
        <div class="blog-post-container mb-15">
            <div class="row">
                @forelse ($posts as $post)
                    <div class="col-lg-4 col-md-6">
                        {{-- ====== single blog post (tipe dinamis) ====== --}}
                        <div class="single-blog-post {{ $post['type'] === 'gallery' ? 'gallery-type-post' : '' }} mb-35">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="single-blog-post-media mb-20">
                                        @if ($post['type'] === 'image')
                                            <div class="image">
                                                <a href="{{ $post['url'] }}">
                                                    <img width="800" height="517" src="{{ $post['images'][0] }}" class="img-fluid"
                                                        alt="{{ $post['title'] }}">
                                                </a>
                                            </div>

                                        @elseif ($post['type'] === 'gallery')
                                            <div class="blog-image-gallery">
                                                {{-- Pakai struktur sederhana; kalau pakai Slick, inisialisasi di JS Anda --}}
                                                @foreach ($post['images'] as $img)
                                                    <div class="single-image">
                                                        <a href="{{ $post['url'] }}">
                                                            <img width="800" height="517" src="{{ $img }}" class="img-fluid"
                                                                alt="{{ $post['title'] }}">
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>

                                        @elseif ($post['type'] === 'audio')
                                            <div class="post-audio ratio ratio-16x9">
                                                <iframe allow="autoplay" src="{{ $post['audio'] }}"></iframe>
                                            </div>

                                        @elseif ($post['type'] === 'video')
                                            <div class="video ratio ratio-16x9">
                                                <iframe width="560" height="315" src="{{ $post['video'] }}" title="Video"
                                                    frameborder="0"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                    allowfullscreen></iframe>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="single-blog-post-content">
                                        <h3 class="post-title">
                                            <a href="{{ $post['url'] }}">{{ $post['title'] }}</a>
                                        </h3>

                                        <div class="post-meta">
                                            <p>
                                                <span><i class="fa fa-user-circle"></i></span>
                                                <a href="#">{{ $post['author'] }}</a>
                                                <span class="separator">/</span>
                                                <span>
                                                    <i class="fa fa-calendar"></i>
                                                    @php
                                                        \Carbon\Carbon::setLocale('id');
                                                        $tgl = \Carbon\Carbon::parse($post['date'])->translatedFormat('d F, Y');
                                                    @endphp
                                                    <a href="#">{{ $tgl }}</a>
                                                </span>
                                            </p>
                                        </div>

                                        <p class="post-excerpt">
                                            {{ \Illuminate\Support\Str::limit($post['excerpt'], 160) }}
                                        </p>

                                        <a href="{{ $post['url'] }}" class="blog-readmore-btn">
                                            lanjut baca <i class="fa fa-long-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- ====== /single blog post ====== --}}
                    </div>
                @empty
                    <div class="col-12">
                        <p class="text-center text-muted">Belum ada postingan.</p>
                    </div>
                @endforelse
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $posts->links('pagination::simple-bootstrap-5') }}
                {{-- atau cukup: {{ $posts->links() }} jika di AppServiceProvider: Paginator::useBootstrapFive(); --}}
            </div>
        </div>
    </div>
@endsection
