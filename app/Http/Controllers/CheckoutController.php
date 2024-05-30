<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Helpers\Cart;
use App\Mail\NewOrderEmail;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Midtrans\Snap;
use Midtrans\Config;
use Midtrans\Notification;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        $user = $request->user();

        // Konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        list($products, $cartItems) = Cart::getProductsAndCartItems();

        $totalPrice = 0;
        $item_details = [];
        foreach ($products as $product) {
            $quantity = $cartItems[$product->id]['quantity'];
            $totalPrice += $product->price * $quantity;
            $item_details[] = [
                'id' => $product->id,
                'price' => $product->price,
                'quantity' => $quantity,
                'name' => $product->title,
            ];
        }

        $transaction_details = [
            'order_id' => uniqid(),
            'gross_amount' => $totalPrice,
        ];

        $customer_details = [
            'first_name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];

        $transactionData = [
            'transaction_details' => $transaction_details,
            'item_details' => $item_details,
            'customer_details' => $customer_details,
        ];

        try {
            $snapToken = Snap::getSnapToken($transactionData);

            // Create Order
            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => $totalPrice,
                'status' => OrderStatus::Unpaid,
            ]);

            // Create Order Items
            foreach ($item_details as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                ]);
            }

            // Create Payment
            Payment::create([
                'order_id' => $order->id,
                'amount' => $totalPrice,
                'status' => PaymentStatus::Pending,
                'type' => 'midtrans',
                'transaction_id' => $transaction_details['order_id'],
            ]);

            CartItem::where(['user_id' => $user->id])->delete();

            return view('checkout.midtrans', compact('snapToken', 'order')); // Pass $order to the view
        } catch (\Exception $e) {
            return view('checkout.failure', ['message' => 'Error creating transaction: ' . $e->getMessage()]);
        }
    }

    public function success(Request $request)
    {
        $orderId = $request->input('order_id');
        $order = Order::findOrFail($orderId);
        $order->status = OrderStatus::Paid;
        $order->save();

        // Update payment status
        $payment = Payment::where('order_id', $order->id)->first();
        if ($payment) {
            $payment->status = PaymentStatus::Paid;
            $payment->save();
        }

        // Send email notifications (optional)
        // $adminUsers = User::where('is_admin', 1)->get();
        // Mail::to($adminUsers)->send(new NewOrderEmail($order, true)); // Email to admin
        // Mail::to($order->user)->send(new NewOrderEmail($order, false)); // Email to customer

        return view('checkout.success', compact('order'));
    }

    public function failure(Request $request)
    {
        return view('checkout.failure'); 
    }

    public function webhook(Request $request)
    {
        // Konfigurasi Midtrans
        Config::$isProduction = config('midtrans.is_production');
        Config::$serverKey = config('midtrans.server_key');

        $notif = new Notification();

        $transaction = $notif->transaction_status;
        $type = $notif->payment_type;
        $orderId = $notif->order_id;
        $fraud = $notif->fraud_status;

        $payment = Payment::where('transaction_id', $orderId)->first();

        if ($transaction == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $payment->status = PaymentStatus::Pending;
                } else {
                    $payment->status = PaymentStatus::Paid;
                }
            }
        } elseif ($transaction == 'settlement') {
            $payment->status = PaymentStatus::Paid;
        } elseif ($transaction == 'pending') {
            $payment->status = PaymentStatus::Pending;
        } elseif ($transaction == 'deny') {
            $payment->status = PaymentStatus::Failed;
        } elseif ($transaction == 'expire') {
            $payment->status = PaymentStatus::Failed;
        } elseif ($transaction == 'cancel') {
            $payment->status = PaymentStatus::Failed;
        }

        $payment->save();

        if ($payment->status == PaymentStatus::Paid) {
            $order = Order::find($payment->order_id);
            $order->status = OrderStatus::Paid;
            $order->save();

            // Send email notifications (optional)
            // $adminUsers = User::where('is_admin', 1)->get();
            // Mail::to($adminUsers)->send(new NewOrderEmail($order, true)); // Email to admin
            // Mail::to($order->user)->send(new NewOrderEmail($order, false)); // Email to customer
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook received successfully'
        ]);
    }
}
