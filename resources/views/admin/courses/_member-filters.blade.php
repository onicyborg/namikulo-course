<form method="GET" action="{{ route('admin.courses.members', $course) }}" class="nc-member-filters" data-member-filters>
    <div><label for="member-page-size">Tampilkan data</label><select id="member-page-size" name="per_page" class="form-control">@foreach([10, 25, 50, 100] as $size)<option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 10) === $size)>{{ $size }} data</option>@endforeach</select></div>
    <div><label for="member-filter-q">Cari nama atau email</label><input type="search" id="member-filter-q" name="q" maxlength="120" class="form-control" placeholder="Cari anggota…" value="{{ $filters['q'] ?? '' }}"></div>
    <div><label for="member-filter-status">Status</label><select id="member-filter-status" name="status" class="form-control"><option value="">Semua status</option>@foreach(\App\Enums\EnrollmentStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
    <a class="nc-member-action nc-member-reset" href="{{ route('admin.courses.members', $course) }}" aria-label="Reset filter" data-action-tooltip="Reset filter"><x-dashboard.icon name="x" /></a>
    <noscript><button class="btn btn-primary" type="submit">Cari anggota</button></noscript>
</form>
