<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$order = \App\Models\Order::latest()->first();
$kasir = \App\Models\User::where('role', 'kasir')->first();

$request = Illuminate\Http\Request::create(
    '/api/orders/' . $order->id,
    'PUT',
    [
        'status' => 'diterima',
        'estimated_ready' => '2026-07-10T10:00:00.000'
    ]
);
$request->headers->set('Accept', 'application/json');
$request->setUserResolver(function () use ($kasir) {
    return $kasir;
});

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
