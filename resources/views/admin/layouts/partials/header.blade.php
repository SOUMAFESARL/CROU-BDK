<nav class="navbar header-navbar pcoded-header">
    <div class="navbar-wrapper" style="display: flex; justify-content: space-between; align-items: center;">
        <div class="navbar-logo" style="display: flex; align-items: center; justify-content: center; width: 240px;">
        

            <a href="#" style="display: flex; align-items: center; justify-content: center;">
                <img class="img-fluid" src="{{ systemLogo() }}" alt="{{ systemConfig('app_name', config('app.name')) }}" width="50" height="50" style="object-fit: contain" />
            </a>
            <a class="mobile-options" style="margin-left: 15px;">
                <i class="feather icon-more-horizontal"></i>
            </a>
        </div>

        <!-- Menu profil à droite -->
        <div class="navbar-container" style="display: flex; align-items: center;">
            <ul class="nav-right" style="margin: 0; padding: 0;">
                <li class="user-profile header-notification">
                    <div class="dropdown-primary dropdown">
                        <div class="dropdown-toggle" data-toggle="dropdown" style="display: flex; align-items: center; cursor: pointer;">
                            <img src="{{ asset('files/assets/images/avatar-4.jpg') }}" class="img-radius" alt="User-Profile-Image">
                            <span style="margin: 0 10px;">{{ getInfoAdmin()->users->name ?? __('ui.guest') }}</span>
                            <i class="feather icon-chevron-down"></i>
                        </div>
                        <ul class="show-notification profile-notification dropdown-menu">
                            <li><a href="{{ route('admin.profile.show') }}"><i class="feather icon-user"></i>{{ __('ui.profile') }}</a></li>
                            <li>
                                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="feather icon-log-out"></i> {{ __('ui.logout') }}
                                </a>
                                @adminroute('admin.logout')
                                <form id="logout-form" action="{{route('admin.logout')}}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                                @endadminroute
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
