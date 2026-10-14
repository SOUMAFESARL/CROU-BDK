@extends('web.layouts.app')

@section('title', 'Forgot Password - Drivic')

@section('content')
    <!-- Breadcrumb or Page Header -->
    <div class="breadcrumb-area bg-overlay" style="background-image: url('{{ asset('assets/img/bg/1.png') }}');">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="breadcrumb-inner text-center">
                        <h2 class="page-title">Forgot Password</h2>
                        <ul class="page-list">
                            <li><a href="{{ route('accueil') }}">Home</a></li>
                            <li>Forgot Password</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Area Start -->
    <div class="forgot-password-area pd-top-120 pd-bottom-120">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-7">
                    <div class="forgot-password-wrap">
                        <div class="section-title text-center">
                            <h2 class="title">Reset Password</h2>
                            <p>Enter your email address and we'll send you a link to reset your password.</p>
                        </div>
                        
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form class="contact-form" action="#" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email Address" value="{{ old('email') }}" required>
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-base w-100">Send Reset Link</button>
                                </div>
                                <div class="col-12 text-center mt-3">
                                    <p>Remember your password? <a href="{{ route('etudiant.login') }}">Sign In</a></p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Forgot Password Area End -->
@endsection
