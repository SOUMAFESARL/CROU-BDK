<!--sidebar menu start-->
<div class="sidebar-menu" id="sidebar-menu">
    <button class="sidebar-menu-close"><i class="fa-solid fa-xmark"></i></button>
    <div class="sidebar-inner">
        <div class="thumb">
            <img src="{{ systemLogo('assets/img/logo.png') }}" alt="{{ systemConfig('app_name', config('app.name')) }}"/>
        </div>
        <p>{{ systemConfig('app_name', config('app.name')) }}</p>
        @if(systemConfig('sector'))<p>{{ systemConfig('sector') }}</p>@endif
        <div class="sidebar-address">
            <h4 class="mb-3">Nous contacter</h4>
            <ul>
                @if(systemConfig('address'))<li><i class="fa-solid fa-location-dot"></i>{{ systemConfig('address') }}</li>@endif
                @if(systemConfig('email'))<li><i class="fa-solid fa-envelope"></i>{{ systemConfig('email') }}</li>@endif
                @if(systemConfig('phone'))<li><i class="fa-solid fa-phone"></i>{{ systemConfig('phone') }}</li>@endif
            </ul>
        </div>
        <div class="sidebar-subscribe">
            <input type="text" placeholder="Email">
            <button><i class="fa-solid fa-angle-right"></i></button>
        </div>
        <ul class="social-media">
            <li><a href="#"><i class="fa-brands fa-facebook-f"></i></a></li>
            <li><a href="#"><i class="fa-brands fa-twitter"></i></a></li>
            <li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
            <li><a href="#"><i class="fa-brands fa-pinterest"></i></a></li>
        </ul>
    </div>
</div>
<!--sidebar menu end-->
