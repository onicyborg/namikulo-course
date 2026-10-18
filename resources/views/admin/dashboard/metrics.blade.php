<section class="nd-overview" aria-labelledby="overview-title">
    <div class="nd-section-heading">
        <h2 id="overview-title">Ikhtisar platform</h2>
        <span class="nd-period"><x-dashboard.icon name="calendar-days" />{{ $monthStart->locale('id')->translatedFormat('F Y') }}</span>
    </div>
    <div class="nd-overview-grid">
        <article class="nd-revenue">
            <div class="nd-revenue-heading"><h3>Pendapatan bulan ini</h3><span class="nd-revenue-icon"><x-dashboard.icon name="wallet" /></span></div>
            <p class="nd-revenue-value">Rp {{ number_format($kpis['revenue'], 0, ',', '.') }}</p>
            <p class="nd-revenue-note">Total pesanan lunas pada {{ $monthStart->locale('id')->translatedFormat('F Y') }}, sesuai zona waktu akun Anda.</p>
            <a href="{{ route('admin.orders.index') }}">Lihat transaksi<x-dashboard.icon name="arrow-right" /></a>
        </article>
        <div class="nd-stats">
            @foreach([
                ['key' => 'pending', 'label' => 'Pesanan menunggu', 'note' => 'Menunggu pembayaran', 'icon' => 'clock-3'],
                ['key' => 'active_enrollments', 'label' => 'Akses belajar aktif', 'note' => 'Akses course yang berlaku', 'icon' => 'graduation-cap'],
                ['key' => 'published', 'label' => 'Course terbit', 'note' => 'Tersedia untuk member', 'icon' => 'book-open'],
                ['key' => 'users', 'label' => 'Total pengguna', 'note' => 'Seluruh akun terdaftar', 'icon' => 'users'],
            ] as $stat)
                <article class="nd-stat">
                    <span class="nd-stat-icon"><x-dashboard.icon :name="$stat['icon']" /></span>
                    <h3>{{ $stat['label'] }}</h3>
                    <p class="nd-stat-value">{{ number_format($kpis[$stat['key']], 0, ',', '.') }}</p>
                    <p class="nd-stat-note">{{ $stat['note'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
