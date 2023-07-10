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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Midtrans\Config;
use Midtrans\Snap;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        Config::$serverKey = config('midtrans.serverKey');
        Config::$clientKey = config('midtrans.clientKey');
        Config::$isProduction = config('midtrans.isProduction');

        [$products, $cartItems] = Cart::getProductsAndCartItems();

        $orderItems = [];
        $lineItems = [];
        $totalPrice = 0;
        foreach ($products as $product) {
            $quantity = $cartItems[$product->id]['quantity'];
            $totalPrice += $product->price * $quantity;
            $lineItems[] = [
                'id' => $product->id,
                'price' => $product->price,
                'quantity' => $quantity,
                'name' => $product->title,
            ];
            $orderItems[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->price,
            ];
        }

        if ($totalPrice < 0.01) {
            // Total price is invalid, handle the error accordingly
            return redirect()->back()->withErrors('Invalid total price');
        }

        $transactionData = [
            'transaction_details' => [
                'order_id' => uniqid(), // Ganti dengan ID pesanan yang sesuai
                'gross_amount' => $totalPrice,
            ],
            'item_details' => $lineItems,
        ];
        
        // This is where you put the new code
        $transactionData['item_details'] = json_encode($transactionData['item_details']);
        
        $transactionData['item_details'] = json_decode($transactionData['item_details']);
        
        if (!is_array($transactionData['item_details'])) {
            // Penanganan jika tipe datanya bukan array
            return redirect()->back()->withErrors('Invalid item details');
        }
        
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
            'type' => 'cc',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'snap_token' => $snapToken,
        ];
        Payment::create($paymentData);

        CartItem::where('user_id', $user->id)->delete();

        return redirect()->away(Snap::getSnapURL($snapToken));
    }

    public function success(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        Config::$serverKey = config('midtrans.serverKey');
        Config::$clientKey = config('midtrans.clientKey');
        Config::$isProduction = config('midtrans.isProduction');

        try {
            $orderId = $request->get('order_id');

            $payment = Payment::query()
                ->where('order_id', $orderId)
                ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Paid])
                ->first();

            if (!$payment) {
                throw new NotFoundHttpException();
            }

            $paymentStatus = Snap::getTransactionStatus($orderId);

            if ($paymentStatus === 'capture') {
                $this->updateOrderAndPayment($payment, PaymentStatus::Paid()->value());
            } else {
                $this->updateOrderAndPayment($payment, PaymentStatus::Failed()->value());
            }

            $order = $payment->order;
            $adminUsers = User::where('is_admin', 1)->get();

            foreach ($adminUsers as $adminUser) {
                Mail::to($adminUser)->send(new NewOrderEmail($order, true));
            }

            Mail::to($order->user)->send(new NewOrderEmail($order, false));

            return view('checkout.success');
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            return view('checkout.failure', ['message' => $e->getMessage()]);
        }
    }

    public function failure(Request $request)
    {
        return view('checkout.failure', ['message' => ""]);
    }

    private function updateOrderAndPayment(Payment $payment, $paymentStatus)
    {
        $payment->status = $paymentStatus;
        $payment->update();

        $order = $payment->order;

        if ($paymentStatus === PaymentStatus::Paid()->value()) {
            $order->status = OrderStatus::Paid;
        } else {
            $order->status = OrderStatus::Failed;
        }

        $order->update();
    }
}
