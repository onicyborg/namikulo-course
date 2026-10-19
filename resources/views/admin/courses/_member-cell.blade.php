@php($effectiveExpired = $enrollment->status === \App\Enums\EnrollmentStatus::Active && $enrollment->expires_at->lessThanOrEqualTo(now()))
@switch($column)
    @case('member')
        <strong>{{ $enrollment->user->name }}</strong><small class="d-block text-muted">{{ $enrollment->user->email }}</small>
        @break
    @case('source')
        {{ $enrollment->source->label() }}
        @break
    @case('starts')
        <time datetime="{{ $enrollment->starts_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $enrollment->starts_at->copy()->utc()->format('d M Y H:i') }} UTC</time>
        @break
    @case('expires')
        <time datetime="{{ $enrollment->expires_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $enrollment->expires_at->copy()->utc()->format('d M Y H:i') }} UTC</time>
        @break
    @case('status')
        <span class="badge badge-{{ $enrollment->status === \App\Enums\EnrollmentStatus::Active && ! $effectiveExpired ? 'success' : 'secondary' }}">{{ $effectiveExpired ? 'Kedaluwarsa' : $enrollment->status->label() }}</span>
        @break
    @case('actions')
        @if($enrollment->status !== \App\Enums\EnrollmentStatus::Revoked)<button type="button" class="nc-member-action nc-table-action nc-table-action--edit" aria-label="Ubah masa berlaku {{ $enrollment->user->name }}" data-action-tooltip="Ubah masa berlaku" data-remote-modal-url="{{ route('admin.members.modal', $enrollment) }}" data-modal-id="member-edit-{{ $enrollment->id }}"><x-dashboard.icon name="calendar-days" /></button><button type="button" class="nc-member-action nc-member-danger nc-table-action nc-table-action--danger" aria-label="Cabut akses {{ $enrollment->user->name }}" data-action-tooltip="Cabut akses" data-remote-modal-url="{{ route('admin.members.modal', $enrollment) }}" data-modal-id="member-revoke-{{ $enrollment->id }}"><x-dashboard.icon name="x" /></button>@else<span class="text-muted">—</span>@endif
        @break
@endswitch
