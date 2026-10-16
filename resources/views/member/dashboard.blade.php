@extends('layouts.member')

@section('title', 'Dashboard Member')

@section('content')
<x-page-header title="Dashboard member" />

<div class="section-body">
    <div class="dashboard-canvas">
        <x-flash-message />
        <div class="row dashboard-grid">
            <div class="col-12 col-lg-7">
                <div class="card dashboard-welcome">
                    <div class="card-body">
                        <p class="dashboard-eyebrow">Area belajar</p>
                        <h2>Halo, {{ auth()->user()->name }}</h2>
                        <p>Informasi akun Anda ada di panel sebelah. Course yang Anda miliki akan tampil di bawah.</p>
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
        <div class="card dashboard-course-state">
            <div class="card-body">
                <x-empty-state
                    title="Belum ada course"
                    description="Course yang Anda miliki akan tampil di sini."
                    icon="book-open"
                />
            </div>
        </div>
    </div>
</div>
@endsection
