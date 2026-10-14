@extends('web.layouts.app')

@section('title', 'Reset Password - Drivic')

@section('content')
    <!-- Breadcrumb or Page Header -->
    <div class="breadcrumb-area bg-overlay" style="background-image: url('{{ asset('assets/img/bg/1.png') }}');">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="breadcrumb-inner text-center">
                        <h2 class="page-title">Reset Password</h2>
                        <ul class="page-list">
                            <li><a href="{{ route('accueil') }}">Home</a></li>
                            <li>Reset Password</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Password Area Start -->
    <div class="reset-password-area pd-top-120 pd-bottom-120">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-7">
                    <div class="reset-password-wrap">
                        <div class="section-title text-center">
                            <h2 class="title">Create New Password</h2>
                            <p>Enter your new password below.</p>
                        </div>

                        <form class="contact-form" action="#" method="POST">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email Address" value="{{ $email ?? old('email') }}" required>
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="New Password" required>
                                        @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm New Password" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-base w-100">Reset Password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Reset Password Area End -->
@endsection
