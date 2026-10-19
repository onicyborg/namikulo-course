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
