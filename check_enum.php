<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Check actual enum values
$col = DB::select("SHOW COLUMNS FROM orders LIKE 'payment_status'");
echo "payment_status column type: " . $col[0]->Type . "\n";

$col2 = DB::select("SHOW COLUMNS FROM orders LIKE 'status'");
echo "status column type: " . $col2[0]->Type . "\n";
