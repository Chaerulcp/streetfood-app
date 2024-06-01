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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Midtrans\Snap;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Transaction;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckoutController extends Controller
{
    
    public function checkout(Request $request)
    {
        $user = $request->user();

        // Midtrans Configuration
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        [$products, $cartItems] = Cart::getProductsAndCartItems();

        $orderItems = [];
        $item_details = [];
        $totalPrice = 0;
        foreach ($products as $product) {
            $quantity = $cartItems[$product->id]['quantity'];
            $totalPrice += $product->price * $quantity;
            $item_details[] = [
                'id' => $product->id,
                'price' => $product->price,
                'quantity' => $quantity,
                'name' => $product->title,
            ];
            $orderItems[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->price
            ];
        }

        // **Add validation before creating the Midtrans transaction:**
        if ($totalPrice <= 0) {
            return back()->with('error', 'Your cart is empty or there is a problem with the total price.');
        }

        $transaction_details = [
            'order_id' => uniqid(), // Unique Order ID
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
            $orderData = [
                'total_price' => $totalPrice,
                'status' => OrderStatus::Unpaid,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ];
            $order = Order::create($orderData);

            // Create Order Items
            foreach ($orderItems as $orderItem) {
                $orderItem['order_id'] = $order->id;
                OrderItem::create($orderItem);
            }

            // Create Payment
            $paymentData = [
                'order_id' => $order->id,
                'amount' => $totalPrice,
                'status' => PaymentStatus::Pending,
                'type' => 'midtrans',
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'transaction_id' => $transaction_details['order_id'] // Store Midtrans transaction ID
            ];
            Payment::create($paymentData);

            CartItem::where(['user_id' => $user->id])->delete();

            return view('checkout.midtrans', compact('snapToken', 'order'));
        } catch (\Exception $e) {
            return view('checkout.failure', ['message' => 'Error creating transaction: ' . $e->getMessage()]);
        }
    }

    public function success(Request $request)
    {
        $orderId = $request->input('order_id');
        $order = Order::findOrFail($orderId);

        // Ideally, you'd verify the transaction here using Midtrans API
        // But for simplicity, we're marking it as paid directly
        $order->status = OrderStatus::Paid;
        $order->save();

        // Update payment status
        $payment = Payment::where('order_id', $order->id)->first();
        if ($payment) {
            $payment->status = PaymentStatus::Paid;
            $payment->save();
        }

        // Send email notifications (if applicable)
        // $adminUsers = User::where('is_admin', 1)->get();
        // foreach ([...$adminUsers, $order->user] as $user) {
        //     Mail::to($user)->send(new NewOrderEmail($order, (bool)$user->is_admin));
        // }

        // ...

        return view('checkout.success', compact('order'));
    }

    public function failure(Request $request)
    {
        return view('checkout.failure', ['message' => ""]);
    }

    public function checkoutOrder(Order $order, Request $request)
    {
        // Midtrans Configuration (same as in checkout)
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        // Validate the Order's Status
        if ($order->isPaid() || in_array($order->status, ['shipped', 'completed', 'cancelled'])) {
            return redirect()->back()->with('error', 'This order cannot be paid.');
        }

        $item_details = [];
        $totalPrice = 0;
        foreach ($order->items as $item) {
            $totalPrice += $item->unit_price * $item->quantity;
            $item_details[] = [
                'id' => $item->product_id,
                'price' => $item->unit_price,
                'quantity' => $item->quantity,
                'name' => $item->product->title,
            ];
        }

        // Check for existing transaction and create a new one if expired
        $orderId = $order->payment->transaction_id ?? uniqid(); 
        
        // Update order's payment if transaction ID was generated here
        if(!$order->payment->transaction_id) {
            $order->payment->update(['transaction_id' => $orderId]);
        }

        // Midtrans Transaction Details
        $transaction_details = [
            'order_id' => $orderId, 
            'gross_amount' => $totalPrice,
        ];

        $customer_details = [
            'first_name' => $order->user->name,
            'email' => $order->user->email,
            'phone' => $order->user->phone,
        ];

        $transactionData = [
            'transaction_details' => $transaction_details,
            'item_details' => $item_details,
            'customer_details' => $customer_details,
        ];

        try {
            $snapToken = Snap::getSnapToken($transactionData);
            return view('checkout.midtrans', compact('snapToken', 'order'));
        } catch (\Exception $e) {
            return view('checkout.failure', ['message' => 'Error creating transaction: ' . $e->getMessage()]);
        }
    }


    public function webhook(Request $request)
    {
        // Midtrans Configuration (same as in checkout)
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        $notif = new Notification();
        $transaction = $notif->transaction_status;
        $type = $notif->payment_type;
        $order_id = $notif->order_id;
        $fraud = $notif->fraud_status;

        $payment = Payment::where('transaction_id', $order_id)->firstOrFail();
        if (!$payment) {
            throw new NotFoundHttpException('Payment Not Found');
        }

        switch ($transaction) {
            case 'capture':
            case 'settlement':
                $payment->status = PaymentStatus::Paid->value;
                break;
            case 'pending':
                $payment->status = PaymentStatus::Pending->value;
                break;
            case 'deny':
            case 'expire':
            case 'cancel':
                $payment->status = PaymentStatus::Failed->value;
                break;
            default:
                Log::info('Unhandled Midtrans Notification Status: ' . $transaction);
                break;
        }

        $payment->update();

        if ($payment->status == PaymentStatus::Paid->value) {
            $this->updateOrderAndSession($payment);
        }

        return response()->json(['status' => 'success', 'message' => 'Webhook received successfully']);

    }    

    private function updateOrderAndSession(Payment $payment)
    {
        $payment->status = PaymentStatus::Paid->value;
        $payment->update();

        $order = $payment->order;

        $order->status = OrderStatus::Paid->value;
        $order->update();
        $adminUsers = User::where('is_admin', 1)->get();

        foreach ([...$adminUsers, $order->user] as $user) {
            Mail::to($user)->send(new NewOrderEmail($order, (bool)$user->is_admin));
        }
    }
}
