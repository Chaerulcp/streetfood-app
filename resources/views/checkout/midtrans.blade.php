<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <script type="text/javascript"
            src="https://app.sandbox.midtrans.com/snap/snap.js"
            data-client-key="{{ config('midtrans.client_key') }}"></script>
</head>
<body>
    <script type="text/javascript">
        // Panggil snap.pay() segera setelah halaman dimuat
        window.onload = function() {
            snap.pay('{{ $snapToken }}', {
                onSuccess: function(result){
                    console.log(result);
                    window.location.href = '/checkout/success?order_id={{ $order->id }}'; // Ganti dengan URL halaman sukses Anda
                },
                onPending: function(result){
                    console.log(result);
                    alert('Pembayaran Anda sedang diproses.');
                },
                onError: function(result){
                    console.log(result);
                    alert('Terjadi kesalahan saat memproses pembayaran Anda.');
                },

                onClose: function() {
                    // Lakukan sesuatu ketika Snap ditutup, misalnya:
                    window.location.href = '{{ route("order.index") }}'; // Redirect ke halaman orders
                }
            });
        };
    </script>
</body>
</html>
