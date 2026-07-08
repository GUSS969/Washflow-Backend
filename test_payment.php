<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

try {
    $kasir = User::where('role', 'kasir')->first();
    $order = Order::latest()->first();

    $payment = DB::transaction(function () use ($kasir, $order) {
        $amount = 50000;
        $method = 'cash';
        $paymentStatus = 'success';
        
        $order->total_paid += $amount;
        if ($order->total_paid >= $order->total_price) {
            $orderPaymentStatus = 'paid';
        } else {
            $orderPaymentStatus = 'partial';
        }

        $payment = Payment::create([
            'order_id'      => $order->id,
            'user_id'       => $kasir->id,
            'method'        => $method,
            'amount'        => $amount,
            'payment_date'  => now(),
            'payment_proof' => null,
            'payment_notes' => 'test',
            'status'        => $paymentStatus,
        ]);

        $order->update([
            'payment_status' => $orderPaymentStatus,
            'total_paid'     => $order->total_paid,
        ]);

        $customerUser = User::where('name', $order->customer->name)->first();
        if ($customerUser) {
            Notification::create([
                'user_id' => $customerUser->id,
                'title'   => 'Pembayaran Dikonfirmasi',
                'message' => "Terima kasih! Pembayaran Anda untuk order {$order->invoice} sudah kami terima.",
            ]);
        }

        return $payment;
    });

    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
