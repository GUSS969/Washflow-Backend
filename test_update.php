<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $order = \App\Models\Order::latest()->first();
    echo "Updating order " . $order->id . "\n";
    $oldStatus = $order->status;
    $order->update([
        'status' => 'diterima',
        'estimated_ready' => '2026-07-10T10:00:00.000'
    ]);
    
    $customer = $order->customer;
    $customerUser = \App\Models\User::where('name', $customer->name)->first();
    if ($customerUser) {
        \App\Models\Notification::create([
            'user_id' => $customerUser->id,
            'title'   => '✅ Pesanan Dikonfirmasi!',
            'message' => "Pesanan Anda ({$order->invoice}) telah dikonfirmasi dan diterima kasir. Cucian sedang dalam antrian.",
        ]);
    }
    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
