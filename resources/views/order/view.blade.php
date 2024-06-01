<x-app-layout>
    <div class="order-detail-container">
        <div class="order-info">
            <h1 class="order-title">Detail Pesanan #{{ $order->id }}</h1>
            <div class="order-date">
                <span class="label">Tanggal Pemesanan:</span>
                <span class="value">{{ $order->created_at->format('d F Y H:i') }}</span>
            </div>
            <div class="order-status">
                <span class="label">Status:</span>
                <span class="status-badge {{ $order->isPaid() ? 'paid' : ($order->status == 'completed' ? 'completed' : ($order->status == 'shipped' ? 'shipped' : 'unpaid')) }}">{{ $order->status }}</span>
            </div>
        </div>

        <table class="order-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="empty-header"></th>
                    <th>Jumlah</th>
                    <th class="text-right">Harga</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items()->with('product')->get() as $item)
                <tr>
                    <td>{{ $item->product->title }}</td>
                    <td>
                        <img src="{{ $item->product->image }}" alt="{{ $item->product->title }}" class="product-image">
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right font-medium">Subtotal:</td>
                    <td class="text-right font-medium">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        
        @if (!$order->isPaid() && !in_array($order->status, ['shipped', 'completed', 'cancelled']))
        <form action="{{ route('checkout.order', $order) }}" method="post" class="payment-form">
            @csrf
            <button type="submit" class="pay-now-button">
                Bayar Sekarang
            </button>
        </form>
        @endif
        <div class="button-container">
            <a href="{{ url()->previous() }}" class="back-button">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
</x-app-layout>

<style>
/* --- Container Utama --- */
.order-detail-container {
    max-width: 700px; /* Lebar container yang lebih sesuai */
    margin: 40px auto;
    padding: 30px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    font-family: 'Poppins', sans-serif;
}

/* --- Informasi Pesanan --- */
.order-info {
    margin-bottom: 30px;
    text-align: left;
}

.order-title {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 10px;
}

.order-date, .order-status {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.label {
    font-weight: 500;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 5px;
    font-weight: 600;
    text-transform: uppercase;
}

.paid { background-color: #28a745; color: white; } /* Hijau */
.unpaid { background-color: #dc3545; color: white; } /* Merah */
.completed { background-color: #28a745; color: white; } /* Hijau */ 
.shipped { background-color: #ffa500; color: white; } /* Oranye */

/* --- Tabel Pesanan --- */
.order-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.order-table th, .order-table td {
    border: 1px solid #dee2e6;
    padding: 12px;
    text-align: left;
}

.order-table th {
    background-color: #f8f9fa; /* Abu-abu muda */
}

.empty-header {
    width: 100px; /* Sesuaikan lebar kolom gambar produk */
}

.product-image {
    max-width: 80px;
    height: auto;
    border-radius: 5px;
}

/* --- Formulir Pembayaran --- */
.payment-form {
    text-align: center;
}

.pay-now-button {
    display: inline-block;
    padding: 12px 24px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 600;
    transition: background-color 0.3s ease;
}

.pay-now-button:hover {
    background-color: #0056b3;
}

/* --- Tombol Kembali --- */
.back-button {
    display: inline-flex;
    align-items: center;
    padding: 12px 20px;
    background-color: #007bff; /* Warna primer (biru) */
    color: white;
    text-decoration: none;
    border: none;          /* Hapus border bawaan */
    border-radius: 25px;   /* Sudut lebih bulat (opsional) */
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Efek bayangan */
}

.back-button:hover {
    background-color: #0056b3; /* Warna biru yang lebih gelap saat dihover */
    transform: translateY(-2px); /* Efek naik sedikit saat dihover */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15); /* Bayangan yang lebih jelas saat dihover */
}



.button-container {
    text-align: center; /* Ratakan tombol ke kiri */
    margin-top: 20px;
}
</style>
