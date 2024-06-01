<x-app-layout>
    <div class="success-container">
        <div class="icon-container">
            <svg xmlns="http://www.w3.org/2000/svg" class="success-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h2 class="success-title">Pembayaran Berhasil!</h2>
        <p class="success-message">
            Selamat, <span class="user-name">{{ auth()->user()->name }}</span>! Pesanan Anda dengan nomor <span class="order-id">#{{ $order->id }}</span> telah berhasil diproses.
        </p>
        <div class="total-payment">
            Total Pembayaran: <span class="payment-amount">Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
        </div>
        <div class="button-container">
            <a href="{{ route('order.view', $order) }}" class="view-detail-button">
                <i class="fas fa-eye"></i> Lihat Detail
            </a>
            <a href="{{ route('products.index') }}" class="continue-shopping-button">
                <i class="fas fa-shopping-cart"></i> Lanjut Belanja
            </a>
        </div>
    </div>
</x-app-layout>

<style>
/* --- Gaya Container Utama --- */
.success-container {
    max-width: 500px;
    margin: 50px auto;
    padding: 40px;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Bayangan yang lebih lembut */
    text-align: center;
    font-family: 'Poppins', sans-serif; /* Menggunakan font yang modern */
}

/* --- Gaya Ikon --- */
/* ... (gaya lainnya) ... */

/* Gaya untuk ikon sukses */
.icon-container {
    display: flex; /* Menggunakan flexbox untuk mengatur posisi */
    justify-content: center; /* Tengahkan secara horizontal */
    align-items: center; /* Tengahkan secara vertikal */
    margin-bottom: 30px;
}

.success-icon {
    width: 80px;
    height: 80px;
    color: #28a745; /* Warna hijau yang lebih cerah */
    animation: pulse 1.2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* --- Gaya Judul --- */
.success-title {
    font-size: 28px; /* Ukuran font yang lebih besar */
    font-weight: 600; /* Lebih tebal */
    color: #212529; /* Warna yang lebih gelap */
    margin-bottom: 15px;
}

/* --- Gaya Pesan --- */
.success-message {
    font-size: 16px;
    color: #495057;
    line-height: 1.6;
}

.user-name, .order-id {
    font-weight: 600;
    color: #007bff; /* Warna biru primer */
}

/* --- Gaya Total Pembayaran --- */
.total-payment {
    background-color: #e2e6ea; /* Warna abu-abu yang lebih modern */
    padding: 20px;
    margin: 30px 0;
    border-radius: 8px;
    font-size: 18px;
}

.payment-amount {
    font-weight: 600;
    color: #004085; /* Warna biru yang lebih gelap */
}

/* --- Gaya Tombol --- */
.button-container {
    display: flex;
    justify-content: center;
    gap: 20px;
}

.view-detail-button, .continue-shopping-button {
    display: inline-flex; /* Untuk mengatur ikon di samping teks */
    align-items: center;
    padding: 14px 28px;
    background-color: #007bff; /* Biru */
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease; /* Transisi semua properti */
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.view-detail-button i,
.continue-shopping-button i {
    margin-right: 8px; /* Jarak antara ikon dan teks */
}


.continue-shopping-button {
    background-color: #28a745; /* Hijau */
}

.view-detail-button:hover,
.continue-shopping-button:hover {
    background-color: #0056b3; /* Biru lebih gelap */
    transform: translateY(-2px); /* Efek angkat saat hover */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

</style>
