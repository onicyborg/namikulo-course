@extends('layouts.member')

@section('title', 'Profil Saya')
@push('styles')<link rel="stylesheet" href="{{ url('css/member-learning.css') }}">@endpush

@section('content')
<x-page-header title="Profil Saya" />
<div class="section-body member-learning">
    <x-flash-message />
    @if($errors->any())<div class="alert alert-danger" role="alert">Periksa kembali data yang ditandai pada formulir.</div>@endif
    <div class="row">
        <div class="col-12 col-lg-7">
            <section class="card" aria-labelledby="profile-info-title"><div class="card-header"><h2 id="profile-info-title">Informasi akun</h2></div><div class="card-body">
                <form method="POST" action="{{ route('member.profile.update') }}" enctype="multipart/form-data" data-loading-form>
                    @csrf @method('PUT')<input type="hidden" name="section" value="profile">
                    <div class="member-profile-avatar">
                        @if($member->avatar_path)<img src="{{ url('storage/'.$member->avatar_path) }}" alt="Avatar {{ $member->name }}" width="72" height="72">@else<span aria-hidden="true">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span>@endif
                        <div><label for="profile-avatar">Foto profil</label><input id="profile-avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="form-control-file {{ $errors->has('avatar') ? 'is-invalid' : '' }}" aria-describedby="profile-avatar-help"><small id="profile-avatar-help" class="form-text text-muted">JPG, PNG, atau WebP. Maksimal 2 MB.</small>@error('avatar')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror</div>
                    </div>
                    <div class="form-group"><label for="profile-name">Nama</label><input id="profile-name" name="name" type="text" value="{{ old('name', $member->name) }}" maxlength="120" required autocomplete="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="form-group"><label for="profile-email">Email</label><input id="profile-email" name="email" type="email" value="{{ $member->email }}" class="form-control" readonly aria-describedby="profile-email-help"><small id="profile-email-help" class="form-text text-muted">Email akun dikelola oleh admin.</small></div>
                    <div class="form-group"><label for="profile-phone">Nomor telepon <span class="text-muted">(opsional)</span></label><input id="profile-phone" name="phone" type="tel" value="{{ old('phone', $member->phone) }}" maxlength="30" autocomplete="tel" class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <button class="btn btn-primary" type="submit">Simpan profil</button>
                </form>
            </div></section>
        </div>
        <div class="col-12 col-lg-5">
            <section class="card" aria-labelledby="profile-password-title"><div class="card-header"><h2 id="profile-password-title">Ubah password</h2></div><div class="card-body">
                <form method="POST" action="{{ route('member.profile.update') }}" data-loading-form>
                    @csrf @method('PUT')<input type="hidden" name="section" value="password">
                    <div class="form-group"><label for="profile-current-password">Password saat ini</label><input id="profile-current-password" name="current_password" type="password" required autocomplete="current-password" class="form-control {{ $errors->has('current_password') ? 'is-invalid' : '' }}">@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="form-group"><label for="profile-new-password">Password baru</label><input id="profile-new-password" name="password" type="password" required minlength="8" autocomplete="new-password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="form-group"><label for="profile-password-confirmation">Ulangi password baru</label><input id="profile-password-confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="form-control"></div>
                    <button class="btn btn-outline-primary" type="submit">Ubah password</button>
                </form>
            </div></section>
        </div>
    </div>
</div>
@endsection
