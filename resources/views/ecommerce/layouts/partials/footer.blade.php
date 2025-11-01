<!--=============================================
    =            Footer         =
    =============================================-->

@php
    $footerBlocks = [
        [
            'title' => 'Need Help?',
            'content' => 'Call: 1-800-345-6789'
        ],
        [
            'title' => 'Products & Sales',
            'content' => 'Call: 1-877-345-6789'
        ],
        [
            'title' => 'Check Order Status',
            'content' => '<a href="my-account.html">Click here</a> to check Order Status.'
        ]
    ];

    $footerLinks = [
        'Products' => [
            'Prices Drop',
            'New Products',
            'Best Sales',
            'Contact Us',
            'My Account'
        ],
        'Our Company' => [
            'Delivery',
            'Legal Notice',
            'About Us',
            'Contact Us',
            'Sitemap',
            'Stores'
        ]
    ];

    $socialLinks = [
        ['url' => '//www.twitter.com', 'icon' => 'fa-twitter'],
        ['url' => '//www.rss.com', 'icon' => 'fa-rss'],
        ['url' => '//plus.google.com', 'icon' => 'fa-google-plus'],
        ['url' => '//www.facebook.com', 'icon' => 'fa-facebook'],
        ['url' => '//www.youtube.com', 'icon' => 'fa-youtube'],
        ['url' => '//www.instagram.com', 'icon' => 'fa-instagram'],
    ];

    $bottomNav = [
        ['label' => 'About Us', 'separator' => '|'],
        ['label' => 'Blog', 'separator' => '|'],
        ['label' => 'My Account', 'separator' => '-'],
        ['label' => 'Order Status', 'separator' => '-'],
        ['label' => 'Shipping & Returns', 'separator' => '-'],
        ['label' => 'Privacy Policy', 'separator' => '-'],
        ['label' => 'Terms & Conditions', 'separator' => ''],
    ];
@endphp

<div class="footer-container pt-60 pb-60">
    <!--=======  footer navigation container  =======-->
    <div class="footer-navigation-container mb-60">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-4 col-md-6 col-sm-6 mb-20 mb-lg-0 mb-xl-0 mb-md-35 mb-sm-35">
                    <div class="single-footer">
                        @foreach($footerBlocks as $block)
                            <div class="single-block mb-35">
                                <h3 class="footer-title">{{ $block['title'] }}</h3>
                                <p>{!! $block['content'] !!}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                @foreach($footerLinks as $title => $links)
                    <div class="col-12 col-lg-2 col-md-6 col-sm-6 mb-20 mb-lg-0 mb-xl-0 mb-md-35 mb-sm-35">
                        <div class="single-footer">
                            <h3 class="footer-title mb-20">{{ $title }}</h3>
                            <ul>
                                @foreach($links as $link)
                                    <li><a href="#">{{ $link }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
                <div class="col-12 col-lg-4 col-md-6 col-sm-6">
                    <div class="single-footer mb-35">
                        <h3 class="footer-title mb-20">Newsletter</h3>
                        <div class="newsletter-form mb-20">
                            <form id="mc-form" class="mc-form subscribe-form">
                                <input type="email" placeholder="Your email address">
                                <button type="submit" value="submit"><i class="lnr lnr-envelope"></i></button>
                            </form>
                        </div>
                        <div class="mailchimp-alerts mb-20">
                            <div class="mailchimp-submitting"></div>
                            <div class="mailchimp-success"></div>
                            <div class="mailchimp-error"></div>
                        </div>
                    </div>
                    <div class="single-footer">
                        <h3 class="footer-title mb-20">Address</h3>
                        <p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Animi corporis, necessitatibus officiis dolor
                            facere ipsum rem sed itaque ea eos.</p>
                        <p>New York</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--=======  End of footer navigation container  =======-->

    <!--=======  footer social link container  =======-->
    <div class="footer-social-link-container pt-15 pb-15 mb-60">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6 col-md-7 mb-sm-15 text-start text-sm-center text-lg-start">
                    <div class="app-download-area">
                        <span class="title">Free App:</span>
                        <a target="_blank" href="#" class="app-download-btn apple-store"><i class="fa fa-apple"></i> Apple Store</a>
                        <a target="_blank" href="#" class="app-download-btn google-play"><i class="fa fa-android"></i> Google play</a>
                    </div>
                </div>
                <div class="col-12 col-lg-6 col-md-5 text-start text-sm-center text-md-end">
                    <div class="social-link">
                        <span class="title">Follow Us:</span>
                        <ul>
                            @foreach($socialLinks as $social)
                                <li><a target="_blank" href="{{ $social['url'] }}"><i class="fa {{ $social['icon'] }}"></i></a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--=======  End of footer social link container  =======-->

    <!--=======  footer bottom navigation  =======-->
    <div class="footer-bottom-navigation text-center mb-20">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="navigation-container">
                        <ul>
                            @foreach($bottomNav as $nav)
                                <li>
                                    <a href="#">{{ $nav['label'] }}</a>
                                    @if($nav['separator'])
                                        <span class="separator">{{ $nav['separator'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--=======  End of footer bottom navigation  =======-->

    <!--=======  copyright section  =======-->
    <div class="copyright-section text-center">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <p class="copyright-text">Copyright &copy; 2021 <a href="index.html">Pataku</a>. All Rights Reserved</p>
                </div>
            </div>
        </div>
    </div>
    <!--=======  End of copyright section  =======-->
</div>
<!--=====  End of Footer  ======-->
