<nav class="navbar navbar-area-1 navbar-area navbar-expand-lg">
    <div class="container nav-container">
        <div class="responsive-mobile-menu">
            <button class="menu toggle-btn d-block d-lg-none" data-target="#edumint_main_menu" 
            aria-expanded="false" aria-label="Toggle navigation">
                <span class="icon-left"></span>
                <span class="icon-right"></span>
            </button>
        </div>
        <div class="logo">
            <a href="{{ route('accueil') }}"><img src="{{ systemLogo() }}" width="70" height="70" alt="{{ systemConfig('app_name', config('app.name')) }}" style="object-fit: contain"></a>
        </div>

        <div class="collapse navbar-collapse" id="edumint_main_menu">
            <ul class="navbar-nav menu-open text-right">



                <li class="nav-item"><a href="{{ route('accueil') }}">{{ __('ui.home') }}</a></li>
                <li class="nav-item"><a href="{{ route('a-propos') }}">{{ __('ui.about') }}</a></li>
                <li class="nav-item"><a href="{{ route('contact') }}">{{ __('ui.contact') }}</a></li>
                <li class="nav-item"><a href="{{ route('login') }}">{{ __('ui.admin_login') }}</a></li>
                <li class="nav-item nav-auth"><a class="btn btn-base-2" href="{{ route('etudiant.login') }}">{{ __('ui.login') }}</a></li>
                @if(systemConfig('public_registration', true) && $result['data']['stats']['active'] ==1)
{{--                <li class="nav-item nav-auth"><a class="btn btn-base" href="{{ route('etudiant.register') }}">Campagne {{$result['data']['campagnes'][0]['annee_campagne']}}</a></li>--}}
                    <li class="nav-item nav-auth"><a class="btn btn-base" href="{{ route('etudiant.register') }}">{{ __('ui.register') }}</a></li>
                @endif
            </ul>
        </div>
        <div class="nav-right-part nav-right-part-desktop">
  </div>
    </div>
</nav>
