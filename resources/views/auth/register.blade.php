@extends('layouts.auth')

@section('title', 'Daftar')

@section('content')
<div class="card auth-card">
    <div class="card-body">
        <div class="auth-heading">
            <p class="auth-eyebrow">Akun member</p>
            <h1>Buat akun pembelajaran</h1>
            <p>Gunakan email aktif untuk akses akun dan pemulihan password.</p>
        </div>

        <x-validation-summary />

        <form method="POST" action="{{ route('register') }}" data-loading-form>
            @csrf
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
                <small id="password-help" class="form-text text-muted">Minimal 8 karakter.</small>
                @error('password')<div id="password-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Ulangi password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control"
                       autocomplete="new-password" minlength="8" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block" data-loading-text="Membuat akun...">Buat Akun</button>
        </form>
    </div>
    <div class="card-footer auth-card-footer">
        Sudah memiliki akun? <a href="{{ route('login') }}">Masuk</a>
    </div>
</div>
@endsection
