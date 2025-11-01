@php
    // ====== Breadcrumb Dinamis ======
    $breadcrumbs = [['label' => 'Beranda', 'url' => url('/')]];
    $segments = request()->segments();
    $url = url('/');
    foreach ($segments as $index => $segment) {
        $url .= '/' . $segment;
        $label = ucwords(str_replace(['-', '_'], ' ', $segment));
        $breadcrumbs[] = [
            'label' => $label,
            'url' => ($index === array_key_last($segments)) ? null : $url,
        ];
    }
@endphp
<div class="breadcrumb-area breadcrumb-bg pt-85 pb-85 mb-80">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-container">
                    <ul>
                        @foreach($breadcrumbs as $crumb)
                            <li @if($loop->last) class="active" @endif>
                                @if(!empty($crumb['url']) && !$loop->last)
                                    <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                                    <span class="separator">/</span>
                                @else
                                    {{ $crumb['label'] }}
                                @endif
                            </li>
                        @endforeach
                        @if(empty($breadcrumbs))
                            <li class="active">Produk</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
