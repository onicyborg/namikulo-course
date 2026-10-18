@extends('layouts.login')

@section('title', 'Lupa Password')
@section('form_title_id', 'forgot-password-title')

@section('content')
<div class="nl-form-content">
    <div class="nl-form-heading">
        <p class="nl-eyebrow">PEMULIHAN AKUN</p>
        <h1 id="forgot-password-title">Atur ulang password</h1>
        <p>Masukkan email akun Anda. Jika terdaftar, kami akan mengirim tautan untuk membuat password baru.</p>
    </div>

    <x-flash-message />
    <x-validation-summary />

    <form method="POST" action="{{ route('password.email') }}" data-loading-form>
        @csrf
        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   autocomplete="email" inputmode="email" required autofocus
                   @error('email') aria-describedby="email-error" aria-invalid="true" @enderror>
            @error('email')<div id="email-error" class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block nl-submit" data-loading-text="Mengirim...">Kirim tautan reset<x-dashboard.icon name="arrow-right" /></button>
    </form>

    <div class="nl-account-switch">Ingat password Anda? <a href="{{ route('login') }}">Kembali ke halaman masuk</a></div>
</div>
@endsection
