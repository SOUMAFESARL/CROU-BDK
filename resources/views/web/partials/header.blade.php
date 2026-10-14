<!-- navbar start -->
<div class="navbar-area">
    <!-- navbar top start -->
    <div class="navbar-top bg-gray">
        <div class="container">
            <div class="row">
                <div class="col-md-8 text-md-left text-center">
                    <ul>
                        @if(systemConfig('phone'))<li><p><i class="fa-solid fa-phone"></i> {{ systemConfig('phone') }}</p></li>@endif
                        @if(systemConfig('email'))<li><p><i class="fa-solid fa-envelope"></i> {{ systemConfig('email') }}</p></li>@endif
                    </ul>
                </div>
                <div class="col-md-4">
                    <ul class="topbar-right text-md-right text-center">
                        <li class="social-area">
                            <a href="#"><i class="fa-brands fa-facebook" aria-hidden="true"></i></a>
                            <a href="#"><i class="fa-brands fa-twitter" aria-hidden="true"></i></a>
                            <a href="#"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a>
                            <a href="#"><i class="fa-brands fa-pinterest" aria-hidden="true"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    @include('web.partials.navigation')
</div>
<!-- navbar end -->
