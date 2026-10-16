@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
<x-page-header title="Dashboard admin" />

<div class="section-body">
    <div class="dashboard-canvas">
        <x-flash-message />
        <div class="row dashboard-grid">
            <div class="col-12 col-lg-7">
                <div class="card dashboard-welcome">
                    <div class="card-body">
                        <p class="dashboard-eyebrow">Area admin</p>
                        <h2>Halo, {{ auth()->user()->name }}</h2>
                        <p>Informasi akun admin Anda ada di panel sebelah. Menu pengelolaan course akan muncul saat tersedia.</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="card dashboard-account">
                    <div class="card-header"><h2>Informasi akun</h2></div>
                    <div class="card-body">
                        <dl class="account-summary">
                            <div><dt>Nama</dt><dd>{{ auth()->user()->name }}</dd></div>
                            <div><dt>Email</dt><dd>{{ auth()->user()->email }}</dd></div>
                            <div><dt>Peran</dt><dd>{{ auth()->user()->role->label() }}</dd></div>
                            <div><dt>Status</dt><dd><span class="badge badge-success">Aktif</span></dd></div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
