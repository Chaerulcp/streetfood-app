<h2>
    Status pesanan Anda diubah menjadi "{{$order->status}}"
</h2>
<p>
    Tautan ke pesanan Anda:
    <a href="{{route('order.view', $order, true)}}">Order #{{$order->id}}</a>
</p>
