@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
<div class="card auth-card">
    <div class="card-body">
        <div class="auth-heading">
            <p class="auth-eyebrow">Selamat datang kembali</p>
            <h1>Masuk ke akun Anda</h1>
            <p>Kelola course atau lanjutkan pembelajaran dari dashboard.</p>
        </div>

        <x-flash-message />
        <x-validation-summary />

        <form method="POST" action="{{ route('login') }}" data-loading-form>
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       autocomplete="email" inputmode="email" required autofocus
                       @error('email') aria-describedby="email-error" aria-invalid="true" @enderror>
                @error('email')<div id="email-error" class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="password">Password</label>
                    <a href="{{ route('password.request') }}" class="auth-inline-link">Lupa password?</a>
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

            <div class="form-group custom-control custom-checkbox">
                <input type="checkbox" name="remember" class="custom-control-input" id="remember" value="1" @checked(old('remember'))>
                <label class="custom-control-label" for="remember">Ingat saya di perangkat ini</label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block" data-loading-text="Memproses...">Masuk</button>
        </form>
    </div>
    <div class="card-footer auth-card-footer">
        Belum memiliki akun? <a href="{{ route('register') }}">Daftar sebagai member</a>
    </div>
</div>
@endsection
