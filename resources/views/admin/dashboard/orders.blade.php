<section class="nd-panel" aria-labelledby="orders-title">
    <div class="nd-panel-heading">
        <div><h2 id="orders-title">Pesanan terbaru</h2><p>Transaksi terakhir di platform Anda.</p></div>
        <a class="nd-text-link" href="{{ route('admin.orders.index') }}">Lihat semua<x-dashboard.icon name="arrow-up-right" /></a>
    </div>
            <x-server-table :rows="$orders" :columns="['order' => 'Pesanan / Member', 'amount' => 'Jumlah', 'status' => 'Status', 'created' => 'Waktu', 'actions' => 'Aksi']" cells="admin.dashboard._order-cell" record="order" :source="route('admin.dashboard.orders.data')" :filters="$filters" sortable="0,1,2,3" order-column="3" direction="desc" />

</section>
