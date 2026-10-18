<form method="GET" action="{{ route('admin.users.index') }}" class="nc-user-filters" data-user-filters>
    <div><label for="users-page-size">Tampilkan data</label><select id="users-page-size" name="per_page" class="form-control">@foreach([10, 25, 50, 100] as $size)<option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 10) === $size)>{{ $size }} data</option>@endforeach</select></div>
    <div><label for="users-search">Cari user</label><input id="users-search" name="q" type="search" maxlength="120" class="form-control" placeholder="Nama atau email…" value="{{ $filters['q'] ?? '' }}"></div>
    <div><label for="users-role">Peran</label><select id="users-role" name="role" class="form-control"><option value="">Semua peran</option>@foreach(\App\Enums\UserRole::cases() as $role)<option value="{{ $role->value }}" @selected(($filters['role'] ?? '') === $role->value)>{{ $role->label() }}</option>@endforeach</select></div>
    <div><label for="users-status">Status</label><select id="users-status" name="status" class="form-control"><option value="">Semua status</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option></select></div>
    <a href="{{ route('admin.users.index') }}" class="nc-data-table-reset" aria-label="Reset filter" data-action-tooltip="Reset filter"><x-dashboard.icon name="x" /></a>
    <noscript><button type="submit" class="btn btn-primary">Cari user</button></noscript>
</form>
