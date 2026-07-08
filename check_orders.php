<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$orders = DB::select("SELECT id, invoice, status, payment_status, total_price, total_paid FROM orders ORDER BY id DESC LIMIT 10");
foreach ($orders as $o) {
    echo "ID:{$o->id} | {$o->invoice} | status={$o->status} | payment={$o->payment_status} | price={$o->total_price} | paid={$o->total_paid}\n";
}
