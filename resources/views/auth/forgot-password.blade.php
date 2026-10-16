@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('content')
<div class="card auth-card">
    <div class="card-body">
        <div class="auth-heading">
            <p class="auth-eyebrow">Pemulihan akun</p>
            <h1>Atur ulang password</h1>
            <p>Masukkan email akun Anda. Kami akan mengirim tautan reset jika email terdaftar.</p>
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
            <button type="submit" class="btn btn-primary btn-lg btn-block" data-loading-text="Mengirim...">Kirim Tautan Reset</button>
        </form>
    </div>
    <div class="card-footer auth-card-footer">
        <a href="{{ route('login') }}">Kembali ke halaman masuk</a>
    </div>
</div>
@endsection
