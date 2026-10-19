@switch($column)
    @case('name')
        <strong>{{ $user->name }}</strong>
        @break
    @case('email')
        {{ $user->email }}
        @break
    @case('role')
        {{ $user->role->label() }}
        @break
    @case('status')
        <span class="badge badge-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
        @break
    @case('access')
        {{ $user->enrollments_count }}
        @break
    @case('actions')
        <div class="nc-data-table-actions">
                <button type="button" class="nc-data-table-action nc-table-action nc-table-action--edit" data-remote-modal-url="{{ route('admin.users.modal', $user) }}" data-modal-id="user-edit-{{ $user->id }}" aria-label="Ubah user {{ $user->name }}" data-action-tooltip="Ubah user"><x-dashboard.icon name="pencil" /></button>
                @if($user->id !== auth()->id())<form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" data-loading-form>@csrf<button type="submit" class="nc-data-table-action nc-table-action {{ $user->is_active ? 'nc-table-action--danger' : 'nc-table-action--success' }}" aria-label="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} user {{ $user->name }}" data-action-tooltip="{{ $user->is_active ? 'Nonaktifkan user' : 'Aktifkan user' }}" data-loading-text="…"><x-dashboard.icon :name="$user->is_active ? 'x' : 'circle-check'" /></button></form>@endif
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" data-loading-form>@csrf<button type="submit" class="nc-data-table-action nc-table-action nc-table-action--warning" aria-label="Kirim tautan reset password ke {{ $user->name }}" data-action-tooltip="Kirim tautan reset password" data-loading-text="…"><x-dashboard.icon name="rotate-ccw" /></button></form>
            </div>
        @break
@endswitch
