@props(['title', 'parent' => null, 'parentUrl' => null, 'description' => null])

@if(request()->routeIs('admin.*', 'member.*'))
    <header class="nd-page-header nd-page-header--legacy">
        <div>
            <nav class="nd-breadcrumb" aria-label="Breadcrumb">
                <span>Namikulo</span><x-dashboard.icon name="chevron-right" /><span>{{ request()->routeIs('admin.*') ? 'Area Admin' : 'Area Member' }}</span>
                @if($parent && $parentUrl && $parent !== 'Dashboard')<x-dashboard.icon name="chevron-right" /><a href="{{ $parentUrl }}">{{ $parent }}</a>@endif
                <x-dashboard.icon name="chevron-right" /><span aria-current="page">{{ $title }}</span>
            </nav>
            <h1>{{ $title }}</h1>
            @if(request()->routeIs('admin.*'))
                @php
                    $adminDescription = $description ?? match (true) {
                        request()->routeIs('admin.categories.*') => 'Atur kategori agar katalog course mudah ditemukan dan dikelola.',
                        request()->routeIs('admin.courses.members*') => 'Kelola anggota, masa berlaku, dan akses belajar dalam satu tempat.',
                        request()->routeIs('admin.courses.curriculum') => 'Susun bab, materi, dan kesiapan video sebelum course diterbitkan.',
                        request()->routeIs('admin.lessons.*') => 'Siapkan materi video dan pantau setiap tahap pemrosesannya.',
                        request()->routeIs('admin.courses.*') => 'Kelola informasi course, kurikulum, dan status penerbitan.',
                        request()->routeIs('admin.discussions.*') => 'Tinjau pertanyaan member dan jaga diskusi tetap relevan dengan materi.',
                        request()->routeIs('admin.notifications.*') => 'Ikuti pembaruan aktivitas dan temukan hal yang perlu ditindaklanjuti.',
                        request()->routeIs('admin.orders.*') => 'Pantau transaksi, status pembayaran, dan detail pesanan member.',
                        request()->routeIs('admin.users.*') => 'Kelola akun, peran, dan status pengguna platform.',
                        request()->routeIs('admin.operations.*') => 'Pantau tugas berkala, pengiriman rekap, dan jejak aktivitas platform.',
                        default => null,
                    };
                @endphp
                @if($adminDescription)<p>{{ $adminDescription }}</p>@endif
            @elseif(request()->routeIs('member.dashboard', 'member.courses.*', 'member.orders.index', 'member.notifications.*', 'member.profile.*'))
                @php
                    $memberDescription = $description ?? match (true) {
                        request()->routeIs('member.dashboard') => 'Lanjutkan belajar dan pantau perkembangan Anda.',
                        request()->routeIs('member.courses.*') => 'Temukan materi, progres belajar, dan masa akses course Anda.',
                        request()->routeIs('member.orders.index') => 'Pantau pembayaran dan unduh invoice transaksi Anda.',
                        request()->routeIs('member.notifications.*') => 'Ikuti pembaruan diskusi, transaksi, dan akses belajar.',
                        default => 'Perbarui informasi pribadi dan keamanan akun Anda.',
                    };
                @endphp
                <p>{{ $memberDescription }}</p>
            @endif
        </div>
    </header>
@else
    <div class="section-header">
        <h1>{{ $title }}</h1>
        @if ($parent && $parentUrl)
            <div class="section-header-breadcrumb" aria-label="Breadcrumb">
                <div class="breadcrumb-item"><a href="{{ $parentUrl }}">{{ $parent }}</a></div>
                <div class="breadcrumb-item active" aria-current="page">{{ $title }}</div>
            </div>
        @endif
    </div>
@endif
