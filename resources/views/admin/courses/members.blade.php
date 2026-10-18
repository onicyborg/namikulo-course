@extends('layouts.admin')
@section('title', 'Anggota · '.$course->title)
@push('styles')
<link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}"><link rel="stylesheet" href="{{ asset('bundles/datatables/datatables.min.css') }}"><link rel="stylesheet" href="{{ asset('bundles/select2/dist/css/select2.min.css') }}"><link rel="stylesheet" href="{{ url('css/content-admin.css') }}"><link rel="stylesheet" href="{{ url('css/admin-members.css') }}">@endpush
@section('content')
<x-page-header title="Anggota course" parent="Course" :parentUrl="route('admin.courses.index')" />
<div class="section-body content-admin nc-members">
    <x-flash-message />
    @if($errors->any())<div class="alert alert-danger" role="alert" tabindex="-1" data-validation-summary>Perubahan belum disimpan: {{ $errors->first() }}</div>@endif
    <div class="card"><div class="card-header content-card-header"><div><p class="content-kicker">AKSES COURSE</p><h2>{{ $course->title }}</h2><p>Berikan akses manual dan kelola masa berlaku anggota.</p></div><div class="card-header-action"><button class="btn btn-primary" type="button" data-toggle="modal" data-target="#member-create">Tambah anggota</button></div></div><div class="card-body">
        @include('admin.courses._member-filters')
        @if($enrollments->isEmpty())<x-empty-state :title="!empty($filters['q']) || !empty($filters['status']) ? 'Tidak ada anggota yang sesuai' : 'Belum ada anggota'" description="Ubah filter atau tambahkan anggota untuk memberikan akses course." />@else
        <div class="table-responsive"><table class="table nc-member-table" @unless($serverSide) id="course-members-table" @endunless><thead><tr><th>Anggota</th><th>Sumber</th><th>Mulai</th><th>Berakhir</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @foreach($enrollments as $enrollment)
            @php($effectiveExpired = $enrollment->status === \App\Enums\EnrollmentStatus::Active && $enrollment->expires_at->lessThanOrEqualTo(now()))
            <tr><td><strong>{{ $enrollment->user->name }}</strong><small class="d-block text-muted">{{ $enrollment->user->email }}</small></td><td>{{ $enrollment->source->label() }}</td><td><time datetime="{{ $enrollment->starts_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $enrollment->starts_at->copy()->utc()->format('d M Y H:i') }} UTC</time></td><td><time datetime="{{ $enrollment->expires_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $enrollment->expires_at->copy()->utc()->format('d M Y H:i') }} UTC</time></td><td><span class="badge badge-{{ $enrollment->status === \App\Enums\EnrollmentStatus::Active && ! $effectiveExpired ? 'success' : 'secondary' }}">{{ $effectiveExpired ? 'Kedaluwarsa' : $enrollment->status->label() }}</span></td><td class="content-actions">@if($enrollment->status !== \App\Enums\EnrollmentStatus::Revoked)<button type="button" class="nc-member-action" aria-label="Ubah masa berlaku {{ $enrollment->user->name }}" data-action-tooltip="Ubah masa berlaku" data-toggle="modal" data-target="#member-edit-{{ $enrollment->id }}"><x-dashboard.icon name="calendar-days" /></button><button type="button" class="nc-member-action nc-member-danger" aria-label="Cabut akses {{ $enrollment->user->name }}" data-action-tooltip="Cabut akses" data-toggle="modal" data-target="#member-revoke-{{ $enrollment->id }}"><x-dashboard.icon name="x" /></button>@else<span class="text-muted">—</span>@endif</td></tr>
        @endforeach
        </tbody></table></div>
        @if($serverSide)<div class="mt-3">{{ $enrollments->links('pagination::bootstrap-4') }}</div>@endif
        @endif
    </div></div>
</div>
@endsection
@push('modals')
<div class="modal fade" id="member-create" tabindex="-1" role="dialog" aria-labelledby="member-create-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form method="POST" action="{{ route('admin.courses.members.store', $course) }}" data-loading-form data-local-time-form>@csrf<input type="hidden" name="timezone" data-browser-timezone><input type="hidden" name="starts_at_utc"><input type="hidden" name="expires_at_utc"><div class="modal-header"><h2 class="modal-title h5" id="member-create-title">Tambah anggota</h2><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><noscript><p class="alert alert-warning">Aktifkan JavaScript untuk mengisi waktu sesuai zona perangkat Anda.</p></noscript>
    <div class="form-group"><label for="member-user">User aktif</label><select id="member-user" name="user_id" class="form-control select2 @error('user_id') is-invalid @enderror" data-member-user-select data-search-url="{{ route('admin.users.search') }}" aria-required="true"><option value=""></option>@foreach($availableMembers as $member)<option value="{{ $member->id }}" data-timezone="{{ $member->timezone }}" @selected(old('user_id') === $member->id)>{{ $member->name }} · {{ $member->email }}</option>@endforeach @if($selectedUser && ! $availableMembers->contains('id', $selectedUser->id))<option value="{{ $selectedUser->id }}" data-timezone="{{ $selectedUser->timezone }}" selected>{{ $selectedUser->name }} · {{ $selectedUser->email }}</option>@endif</select><small class="form-text text-muted">Klik untuk melihat member aktif, atau ketik nama maupun email untuk mencari.</small><small class="form-text text-muted" data-member-timezone-hint>Durasi enam bulan mengikuti zona waktu member yang terakhir terdeteksi.</small>@if($availableMembers->isEmpty())<small class="form-text text-warning">Belum ada akun berperan Member yang aktif. Tambahkan atau aktifkan akun di halaman User.</small>@endif @error('user_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
    <div class="form-group"><label for="member-starts">Mulai akses</label><input id="member-starts" type="datetime-local" name="starts_at" data-local-datetime-input data-utc="{{ now()->utc()->toIso8601String() }}" data-default-now="{{ old('starts_at') ? 'false' : 'true' }}" class="form-control @error('starts_at') is-invalid @enderror" value="{{ old('starts_at') }}" required><small class="form-text text-muted">Jam mengikuti perangkat Anda. Akhir akses otomatis enam bulan menurut zona member bila dikosongkan.</small>@error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="form-group"><label for="member-expires">Berakhir <span class="text-muted">(opsional)</span></label><input id="member-expires" type="datetime-local" name="expires_at" data-local-datetime-input class="form-control @error('expires_at') is-invalid @enderror" value="{{ old('expires_at') }}">@error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="form-group mb-0"><label for="member-note">Catatan <span class="text-muted">(opsional)</span></label><input id="member-note" name="note" class="form-control @error('note') is-invalid @enderror" maxlength="255" value="{{ old('_modal') === 'member-create' ? old('note') : '' }}">@error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><input type="hidden" name="_modal" value="member-create">
</div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Berikan akses</button></div></form></div></div></div>
@foreach($enrollments as $enrollment)
@if($enrollment->status !== \App\Enums\EnrollmentStatus::Revoked)
@php($editing = old('_modal') === 'member-edit-'.$enrollment->id)
<div class="modal fade" id="member-edit-{{ $enrollment->id }}" tabindex="-1" role="dialog" aria-labelledby="member-edit-title-{{ $enrollment->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form method="POST" action="{{ route('admin.members.update', $enrollment) }}" data-loading-form data-local-time-form>
        @csrf @method('PUT')
        <input type="hidden" name="timezone" data-browser-timezone><input type="hidden" name="expires_at_utc">
        <div class="modal-header"><h2 class="modal-title h5" id="member-edit-title-{{ $enrollment->id }}">Ubah akhir akses · {{ $enrollment->user->name }}</h2><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div>
        <div class="modal-body"><noscript><p class="alert alert-warning">Aktifkan JavaScript untuk mengubah waktu sesuai zona perangkat Anda.</p></noscript>
            <div class="form-group"><label for="member-edit-expiry-{{ $enrollment->id }}">Berakhir</label><input id="member-edit-expiry-{{ $enrollment->id }}" type="datetime-local" name="expires_at" data-local-datetime-input data-utc="{{ $enrollment->expires_at->copy()->utc()->toIso8601String() }}" class="form-control @if($editing) @error('expires_at') is-invalid @enderror @endif" value="{{ $editing ? old('expires_at') : '' }}" required><small class="form-text text-muted">Jam mengikuti perangkat Anda.</small>@if($editing) @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
            <div class="form-group mb-0"><label for="member-edit-note-{{ $enrollment->id }}">Alasan perubahan</label><input id="member-edit-note-{{ $enrollment->id }}" name="note" class="form-control @if($editing) @error('note') is-invalid @enderror @endif" maxlength="255" value="{{ $editing ? old('note') : '' }}" required>@if($editing) @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
            <input type="hidden" name="_modal" value="member-edit-{{ $enrollment->id }}">
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan perubahan</button></div>
    </form></div></div>
</div>
@php($revoking = old('_modal') === 'member-revoke-'.$enrollment->id)
<div class="modal fade" id="member-revoke-{{ $enrollment->id }}" tabindex="-1" role="dialog" aria-labelledby="member-revoke-title-{{ $enrollment->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form method="POST" action="{{ route('admin.members.revoke', $enrollment) }}" data-loading-form>
        @csrf
        <div class="modal-header"><h2 class="modal-title h5" id="member-revoke-title-{{ $enrollment->id }}">Cabut akses · {{ $enrollment->user->name }}</h2><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div>
        <div class="modal-body"><p>Akses course ini akan segera berakhir untuk anggota tersebut.</p><div class="form-group mb-0"><label for="member-revoke-note-{{ $enrollment->id }}">Alasan pencabutan</label><input id="member-revoke-note-{{ $enrollment->id }}" name="note" class="form-control @if($revoking) @error('note') is-invalid @enderror @endif" maxlength="255" value="{{ $revoking ? old('note') : '' }}" required>@if($revoking) @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div><input type="hidden" name="_modal" value="member-revoke-{{ $enrollment->id }}"></div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Cabut akses</button></div>
    </form></div></div>
</div>
@endif
@endforeach
@endpush
@push('plugin-scripts')<script src="{{ asset('bundles/datatables/datatables.min.js') }}"></script><script src="{{ asset('bundles/select2/dist/js/select2.full.min.js') }}"></script>@endpush
@push('scripts')
<script src="{{ url('js/admin-action-tooltip.js') }}"></script><script src="{{ url('js/pages/admin-member-list.js') }}"></script><script src="{{ url('js/pages/admin-content.js') }}"></script><script src="{{ url('js/pages/admin-members.js') }}?v=3"></script>@if($errors->any() && old('_modal'))<script>window.contentModalToOpen = @json(old('_modal'));</script>@endif @endpush
