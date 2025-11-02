@extends('ecommerce.layouts.app')
@section('title', (isset($post['title']) ? $post['title'] : 'Artikel'))

@section('content')
    @include('ecommerce.layouts.partials.breadscrum')

    @php
        \Carbon\Carbon::setLocale('id');
        // URL detail aman tanpa named route:
        $shareUrl = $post['url'] ?? url('/articles/' . ($post['slug'] ?? ''));
    @endphp

    <div class="container">
        <div class="row">
            <div class="col-lg-3 order-2 order-lg-1">
                <!--=======  sidebar  =======-->
                <div class="sidebar-container shop-sidebar-container">

                    {{-- Search --}}
                    <div class="single-sidebar-widget mb-30">
                        <h3 class="sidebar-title">Search</h3>
                        <form class="sidebar-search-box" action="{{ url('/articles') }}" method="GET">
                            <input type="search" name="q" placeholder="Search" value="{{ request('q') }}">
                            <button type="submit"><i class="fa fa-search"></i></button>
                        </form>
                    </div>

                    {{-- Categories (dinamis sederhana) --}}
                    <div class="single-sidebar-widget mb-30">
                        @php
                            $categories = \App\Models\Blog\ArticleCategory::query()->orderBy('name')->get();
                        @endphp
                        <h3 class="sidebar-title">Categories</h3>
                        <ul class="category-dropdown">
                            @foreach ($categories as $category)
                                @php
                                    $children = \App\Models\Blog\ArticleCategory::where('parent_id', $category->id)->orderBy('name')->get();
                                @endphp
                                <li class="{{ $children->isNotEmpty() ? 'has-children' : '' }}">
                                    <a href="{{ url('/articles?category=' . $category->slug) }}">{{ $category->name }}</a>
                                    @if ($children->isNotEmpty())
                                        <ul>
                                            @foreach($children as $child)
                                                <li>
                                                    <a href="{{ url('/articles?category=' . $child->slug) }}">{{ $child->name }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <a href="#" class="expand-icon">+</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Recent Posts --}}
                    <div class="single-sidebar-widget mb-30">
                        <h3 class="sidebar-title">Artikel lainnya</h3>
                        <div class="block-container">
                            @forelse($recent as $rp)
                                <div class="single-block d-flex">
                                    <div class="image">
                                        <a href="{{ $rp['url'] ?? url('/articles/' . ($rp['slug'] ?? '')) }}">
                                            <img width="500" height="500"
                                                 src="{{ $rp['images'][0] ?? 'https://picsum.photos/seed/na/500/500' }}"
                                                 class="img-fluid" alt="{{ $rp['title'] ?? 'Post' }}">
                                        </a>
                                    </div>
                                    <div class="content">
                                        <p>
                                            <a href="{{ $rp['url'] ?? url('/articles/' . ($rp['slug'] ?? '')) }}">
                                                {{ $rp['title'] ?? 'Untitled' }}
                                            </a>
                                            <span>
                                                {{ isset($rp['date']) ? \Carbon\Carbon::parse($rp['date'])->translatedFormat('d F, Y') : '' }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted">Tidak ada</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Tags (dinamis dari master tag) --}}
                    <div class="single-sidebar-widget">
                        <h3 class="sidebar-title">Tags</h3>
                        <ul class="tag-container">
                            @php
                                $tags = \App\Models\Blog\ArticleTag::orderBy('name')->get();
                            @endphp
                            @forelse($tags as $t)
                                <li>
                                    <a href="{{ url('/articles?tag=' . $t->slug) }}">
                                        {{ $t->name }}
                                    </a>
                                </li>
                            @empty
                                <li><span class="text-muted">None</span></li>
                            @endforelse
                        </ul>
                    </div>

                </div>
                <!--=======  End of sidebar  =======-->
            </div>

            <div class="col-lg-9 order-1 order-lg-2">
                <!--=======  blog post container  =======-->
                <div class="blog-single-post-container mb-80">
                    @if(empty($post))
                        <h3 class="post-title">Article not found</h3>
                    @else
                        {{-- Title --}}
                        <h3 class="post-title">{{ $post['title'] }}</h3>

                        {{-- Meta --}}
                        <div class="post-meta">
                            <p>
                                <span><i class="fa fa-user-circle"></i> Posted By: </span>
                                <a href="#">{{ $post['author'] ?? 'Admin' }}</a>
                                <span class="separator">/</span>
                                <span><i class="fa fa-calendar"></i> Posted On:
                                    <a href="#">
                                        {{ isset($post['date'])
                                            ? \Carbon\Carbon::parse($post['date'])->translatedFormat('d F, Y')
                                            : (isset($post['published_at'])
                                                ? \Carbon\Carbon::parse($post['published_at'])->translatedFormat('d F, Y')
                                                : 'Unknown') }}
                                    </a>
                                </span>
                            </p>
                        </div>

                        {{-- Media --}}
                        <div class="single-blog-post-media mb-xs-20">
                            @switch($post['type'] ?? 'image')
                                @case('gallery')
                                    <div class="blog-image-gallery">
                                        @foreach(($post['images'] ?? []) as $img)
                                            <div class="single-image mb-2">
                                                <img width="800" height="517" src="{{ $img }}" class="img-fluid" alt="{{ $post['title'] }}">
                                            </div>
                                        @endforeach
                                    </div>
                                @break

                                @case('audio')
                                    <div class="post-audio ratio ratio-16x9">
                                        <iframe allow="autoplay" src="{{ $post['audio'] }}"></iframe>
                                    </div>
                                @break

                                @case('video')
                                    <div class="video ratio ratio-16x9">
                                        <iframe width="560" height="315" src="{{ $post['video'] }}" title="YouTube video player" frameborder="0"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowfullscreen></iframe>
                                    </div>
                                @break

                                @default
                                    <div class="image">
                                        <img width="800" height="517"
                                             src="{{ $post['images'][0] ?? 'https://picsum.photos/seed/fallback/800/517' }}"
                                             class="img-fluid" alt="{{ $post['title'] }}">
                                    </div>
                            @endswitch
                        </div>

                        {{-- Content --}}
                        <div class="post-content mb-40">
                            @if(!empty($post['excerpt']))
                                <p>{{ $post['excerpt'] }}</p>
                            @endif
                            {{-- jika punya body HTML, bisa render di sini: {!! $post['body'] ?? '' !!} --}}
                        </div>

                        {{-- Tags (opsional jika mau tampilkan dari master tag yang terkait) --}}
                        {{-- abaikan jika $tags sidebar sudah cukup --}}

                        {{-- Share --}}
                        <div class="social-share-buttons mb-40">
                            <h3>share this post</h3>
                            <ul>
                                <li><a class="twitter"   target="_blank" href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($post['title'] ?? '') }}"><i class="fa fa-twitter"></i></a></li>
                                <li><a class="facebook"  target="_blank" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"><i class="fa fa-facebook"></i></a></li>
                                <li><a class="pinterest" target="_blank" href="https://pinterest.com/pin/create/button/?url={{ urlencode($shareUrl) }}&description={{ urlencode($post['title'] ?? '') }}"><i class="fa fa-pinterest"></i></a></li>
                                <li><a class="linkedin"  target="_blank" href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}"><i class="fa fa-linkedin"></i></a></li>
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
