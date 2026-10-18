@extends('layouts.login')

@section('title', 'Daftar')
@section('form_title_id', 'register-title')

@section('content')
<div class="nl-form-content">
    <div class="nl-form-heading">
        <p class="nl-eyebrow">MULAI BELAJAR</p>
        <h1 id="register-title">Buat akun member</h1>
        <p>Simpan progres belajar dan diskusi Anda dalam satu akun.</p>
    </div>

    <x-flash-message />
    <x-validation-summary />

    <form method="POST" action="{{ route('register') }}" data-loading-form>
        @csrf
        <input type="hidden" name="timezone" data-browser-timezone>

        <div class="form-group">
            <label for="name">Nama lengkap</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                   class="form-control @error('name') is-invalid @enderror"
                   autocomplete="name" maxlength="120" required autofocus
                   @error('name') aria-describedby="name-error" aria-invalid="true" @enderror>
            @error('name')<div id="name-error" class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   autocomplete="email" inputmode="email" required
                   @error('email') aria-describedby="email-error" aria-invalid="true" @enderror>
            @error('email')<div id="email-error" class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-field">
                <input id="password" type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       autocomplete="new-password" minlength="8" required
                       aria-describedby="password-help @error('password') password-error @enderror"
                       @error('password') aria-invalid="true" @enderror>
                <button type="button" class="password-toggle" data-password-toggle="password" aria-controls="password" aria-label="Tampilkan password" aria-pressed="false">Tampilkan</button>
            </div>
            <small id="password-help" class="form-text">Minimal 8 karakter.</small>
            @error('password')<div id="password-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">Konfirmasi password</label>
            <div class="password-field">
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="form-control @error('password_confirmation') is-invalid @enderror"
                       autocomplete="new-password" minlength="8" required
                       @error('password_confirmation') aria-describedby="password-confirmation-error" aria-invalid="true" @enderror>
                <button type="button" class="password-toggle" data-password-toggle="password_confirmation" aria-controls="password_confirmation" aria-label="Tampilkan password" aria-pressed="false">Tampilkan</button>
            </div>
            @error('password_confirmation')<div id="password-confirmation-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block nl-submit" data-loading-text="Membuat akun...">Buat akun<x-dashboard.icon name="arrow-right" /></button>
    </form>

    <div class="nl-account-switch">
        Sudah memiliki akun? <a href="{{ route('login') }}">Masuk</a>
    </div>
</div>
@endsection
