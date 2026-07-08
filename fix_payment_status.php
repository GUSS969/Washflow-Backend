<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;

// Update semua order yang total_paid >= total_price menjadi 'lunas'
$orders = Order::all();
$fixed = 0;
foreach ($orders as $order) {
    $correctStatus = $order->total_paid >= $order->total_price && $order->total_price > 0 ? 'lunas' : 'belum_lunas';
    if ($order->payment_status !== $correctStatus) {
        $order->update(['payment_status' => $correctStatus]);
        echo "Fixed order {$order->invoice}: {$order->payment_status} -> {$correctStatus} (total_paid={$order->total_paid}, total_price={$order->total_price})\n";
        $fixed++;
    }
}
echo "Total fixed: $fixed orders\n";
