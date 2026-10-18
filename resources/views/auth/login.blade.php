@extends('layouts.login')

@section('title', 'Masuk')

@section('content')
<div class="nl-form-content">
        <div class="nl-form-heading">
            <p class="nl-eyebrow">SELAMAT DATANG KEMBALI</p>
            <h1 id="login-title">Masuk ke akun Anda</h1>
            <p>Lanjutkan belajar atau kelola course Anda.</p>
        </div>

        <x-flash-message />
        <x-validation-summary />

        <form method="POST" action="{{ route('login') }}" data-loading-form>
            @csrf
            <input type="hidden" name="timezone" data-browser-timezone>
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       autocomplete="email" inputmode="email" required autofocus
                       @error('email') aria-describedby="email-error" aria-invalid="true" @enderror>
                @error('email')<div id="email-error" class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <div class="nl-password-label">
                    <label for="password">Password</label>
                    <a href="{{ route('password.request') }}">Lupa password?</a>
                </div>
                <div class="password-field">
                    <input id="password" type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           autocomplete="current-password" required
                           @error('password') aria-describedby="password-error" aria-invalid="true" @enderror>
                    <button type="button" class="password-toggle" data-password-toggle="password" aria-controls="password" aria-label="Tampilkan password" aria-pressed="false">Tampilkan</button>
                </div>
                @error('password')<div id="password-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
            </div>

            <div class="nl-remember">
                <input type="checkbox" name="remember" id="remember" value="1" @checked(old('remember'))>
                <label for="remember">Ingat saya di perangkat ini</label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block nl-submit" data-loading-text="Memproses...">Masuk<x-dashboard.icon name="arrow-right" /></button>
        </form>
    <div class="nl-account-switch">
        Belum memiliki akun? <a href="{{ route('register') }}">Daftar sebagai member</a>
    </div>
</div>
@endsection
