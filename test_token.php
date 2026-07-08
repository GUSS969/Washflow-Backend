<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$kasir = \App\Models\User::where('role', 'kasir')->first();
$token = $kasir->createToken('test')->plainTextToken;

echo $token;
