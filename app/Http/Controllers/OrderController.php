<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // Ambil pesanan yang dibuat oleh pengguna saat ini dan urutkan dari yang terbaru ke terlama
        $orders = Order::withCount('items')
                        ->where('created_by', Auth::id())
                        ->orderBy('created_at', 'desc')
                        ->paginate(10); // Atau sesuaikan jumlah item per halaman

        return view('order.index', compact('orders'));
    }

    public function view(Order $order)
    {
        // Periksa apakah pesanan milik pengguna saat ini
        if ($order->created_by !== Auth::id()) {
            abort(403, 'Unauthorized action.'); 
        }

        return view('order.view', compact('order'));
    }
}
