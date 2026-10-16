@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="card auth-card">
    <div class="card-body">
        <div class="auth-heading">
            <p class="auth-eyebrow">Password baru</p>
            <h1>Amankan kembali akun Anda</h1>
            <p>Buat password baru dengan minimal 8 karakter.</p>
        </div>

        <x-validation-summary />

        <form method="POST" action="{{ route('password.update') }}" data-loading-form>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                       class="form-control @error('email') is-invalid @enderror"
                       autocomplete="email" required autofocus
                       @error('email') aria-describedby="email-error" aria-invalid="true" @enderror>
                @error('email')<div id="email-error" class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="password">Password baru</label>
                <div class="password-field">
                    <input id="password" type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           autocomplete="new-password" minlength="8" required
                           @error('password') aria-describedby="password-error" aria-invalid="true" @enderror>
                    <button type="button" class="password-toggle" data-password-toggle="password" aria-controls="password" aria-label="Tampilkan password" aria-pressed="false">Tampilkan</button>
                </div>
                @error('password')<div id="password-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Ulangi password baru</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control"
                       autocomplete="new-password" minlength="8" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block" data-loading-text="Menyimpan...">Simpan Password Baru</button>
        </form>
    </div>
</div>
@endsection
