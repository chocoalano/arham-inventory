<div class="ptk-product" wire:key="product-card-{{ $productId ?? \Illuminate\Support\Str::random(6) }}">
    @if (session()->has('success'))
        <div x-data x-init="setTimeout(() => $el.remove(), 2200)" class="alert alert-success text-sm mb-2">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('info'))
        <div x-data x-init="setTimeout(() => $el.remove(), 2200)" class="alert alert-info text-sm mb-2">
            {{ session('info') }}
        </div>
    @endif

    <div class="image">
        <a href="{{ $url }}">
            <img width="300" height="360" src="{{ $img }}" class="img-fluid" alt="{{ $name ?? 'Produk' }}">
        </a>

        {{-- Preview --}}
        <a class="hover-icon" href="{{ $url }}"><i class="lnr lnr-eye"></i></a>

        {{-- Wishlist --}}
        <a class="hover-icon" href="#" title="{{ $isWishlisted ? 'Hapus dari Wishlist' : 'Tambah ke Wishlist' }}"
            wire:click.prevent="toggleWishlist">
            <i class="lnr lnr-heart @if($isWishlisted) text-danger @endif"></i>
        </a>

        {{-- Add to cart --}}
        <a class="hover-icon" href="#" title="Tambah ke Keranjang" wire:click.prevent="addToCart">
            <i class="lnr lnr-cart"></i>
        </a>

        {{-- Badges --}}
        @if(!empty($badges))
            <div class="product-badge">
                @foreach($badges as $badge)
                    @if($badge === 'BARU')
                        <span class="new-badge">{{ $badge }}</span>
                    @elseif(\Illuminate\Support\Str::contains($badge, '%'))
                        <span class="discount-badge">{{ $badge }}</span>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    <div class="content">
        <p class="product-title">
            <a href="{{ $url }}">{{ $name ?? 'Produk' }}</a>
        </p>

        <p class="product-price">
            @if($this->hasDiscount)
                <span class="main-price discounted">
                    Rp{{ number_format((float) $compare, 0, ',', '.') }}
                </span>
                <span class="discounted-price">
                    Rp{{ number_format((float) $price, 0, ',', '.') }}
                </span>
            @else
                <span class="main-price">
                    Rp{{ number_format((float) $price, 0, ',', '.') }}
                </span>
            @endif
        </p>
    </div>
</div>
