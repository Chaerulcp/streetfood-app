<x-app-layout>
    <div class="orders-container">
        <h1 class="orders-title">Pesanan Saya</h1>

        <div class="orders-table-container">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Subtotal</th>
                        <th>Item</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td><a href="{{ route('order.view', $order) }}" class="order-link">#{{ $order->id }}</a></td>
                        <td>{{ $order->created_at->format('d F Y H:i') }}</td>
                        <td>
                            <span class="status-badge {{ $order->isPaid() ? 'paid' : ($order->status == 'completed' ? 'completed' : ($order->status == 'shipped' ? 'shipped' : 'unpaid')) }}">{{ $order->status }}</span>
                        </td>
                        <td>Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                        <td>{{ $order->items_count }} item(s)</td>
                        <td>
                            @if (!$order->isPaid() && !in_array($order->status, ['shipped', 'completed', 'cancelled']))
                            <form action="{{ route('checkout.order', $order) }}" method="post">
                                @csrf
                                <button type="submit" class="pay-now-button">
                                    <i class="fas fa-credit-card"></i> Bayar
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>

<style>
/* --- Container Utama --- */
.orders-container {
    max-width: 900px; /* Lebar container yang lebih sesuai */
    margin: 40px auto;
    padding: 30px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    font-family: 'Poppins', sans-serif;
}

/* --- Judul --- */
.orders-title {
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 20px;
    text-align: center; /* Judul berada di tengah */
}

/* --- Tabel Pesanan --- */
.orders-table-container {
    overflow-x: auto; /* Tambahkan scroll horizontal jika tabel terlalu lebar */
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.orders-table th, .orders-table td {
    border: 1px solid #dee2e6;
    padding: 12px;
    text-align: left;
}

.orders-table th {
    background-color: #f8f9fa;
    font-weight: 500;
}

/* --- Status Badge --- */
.status-badge {
    padding: 5px 10px;
    border-radius: 5px;
    font-weight: 600;
    text-transform: capitalize; /* Hanya huruf pertama yang kapital */
}

.paid { background-color: #28a745; color: white; }
.unpaid { background-color: #dc3545; color: white; }
.completed { background-color: #28a745; color: white; } /* Hijau */ 
.shipped { background-color: #ffa500; color: white; } /* Oranye */

/* --- Link Pesanan --- */
.order-link {
    color: #007bff;
    text-decoration: none;
}

.order-link:hover {
    text-decoration: underline;
}

/* --- Tombol Bayar --- */
.pay-now-button {
    display: inline-flex; /* Untuk mengatur ikon di samping teks */
    align-items: center;
    padding: 8px 16px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.pay-now-button:hover {
    background-color: #0056b3;
}

.pay-now-button i {
    margin-right: 5px;
}

/* --- Pagination --- */
.pagination-container {
    text-align: center;
    margin-top: 20px;
}
</style>
