<?php

// app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'payment_status',
        'payment_id', // ID transaksi dari Midtrans
        'payment_amount',
        'fraud_status', // (Opsional) Status fraud dari Midtrans
        'settlement_time', // (Opsional) Waktu settlement dari Midtrans
        // Tambahkan kolom lain sesuai kebutuhan (misalnya: bank, va_number, dll.)
    ];

    // Relasi dengan model Order (jika ada)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
