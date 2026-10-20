<section class="nd-panel na-workspace-links" aria-labelledby="workspace-links-title">
    <div class="nd-panel-heading"><div><h2 id="workspace-links-title">Ruang kerja</h2><p>Akses langsung ke pengelolaan harian.</p></div></div>
    @foreach([
        ['route' => 'admin.discussions.index', 'icon' => 'message-square', 'title' => 'Diskusi materi', 'description' => 'Tinjau pertanyaan dan balasan member'],
        ['route' => 'admin.users.index', 'icon' => 'users', 'title' => 'Pengguna', 'description' => 'Kelola akun dan status pengguna'],
        ['route' => 'admin.operations.index', 'icon' => 'activity', 'title' => 'Tugas & log', 'description' => 'Periksa tugas dan aktivitas sistem'],
    ] as $link)
        <a href="{{ route($link['route']) }}"><span class="na-workspace-link-icon"><x-dashboard.icon :name="$link['icon']" /></span><span><strong>{{ $link['title'] }}</strong><small>{{ $link['description'] }}</small></span><x-dashboard.icon name="arrow-up-right" /></a>
    @endforeach
</section>
