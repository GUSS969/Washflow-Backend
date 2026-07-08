<?php
/**
 * ===================================================
 * SCRIPT FIX DATABASE - Jalankan sekali lewat browser
 * Buka: http://localhost:8000/fix_db.php
 * atau http://127.0.0.1:8000/fix_db.php
 * ===================================================
 * HAPUS FILE INI SETELAH SELESAI!
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$log = [];
$errors = [];

function runSql($label, $sql) {
    global $log, $errors;
    try {
        DB::statement($sql);
        $log[] = "✅ " . $label;
    } catch (\Exception $e) {
        $errors[] = "⚠️ " . $label . " → " . $e->getMessage();
    }
}

function runUpdate($label, $table, $where, $set) {
    global $log, $errors;
    try {
        $count = DB::table($table)->where($where[0], $where[1])->update($set);
        $log[] = "✅ " . $label . " ($count rows)";
    } catch (\Exception $e) {
        $errors[] = "⚠️ " . $label . " → " . $e->getMessage();
    }
}

// =============================================
// 1. Hapus record migration yang gagal
// =============================================
try {
    DB::table('migrations')
        ->where('migration', '2026_07_07_000001_update_payments_and_orders_schema')
        ->delete();
    $log[] = "✅ Hapus record migration gagal";
} catch (\Exception $e) {
    $errors[] = "⚠️ Hapus migration record → " . $e->getMessage();
}

// =============================================
// 2. Fix kolom payment_status di tabel orders
// =============================================
runSql("Convert payment_status ke VARCHAR", "ALTER TABLE orders MODIFY COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'unpaid'");
runUpdate("Update belum_lunas → unpaid",    'orders', ['payment_status', 'belum_lunas'], ['payment_status' => 'unpaid']);
runUpdate("Update menunggu_konfirmasi → partial", 'orders', ['payment_status', 'menunggu_konfirmasi'], ['payment_status' => 'partial']);
runUpdate("Update lunas → paid",            'orders', ['payment_status', 'lunas'], ['payment_status' => 'paid']);
runSql("Convert payment_status ke ENUM baru", "ALTER TABLE orders MODIFY COLUMN payment_status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid'");

// =============================================
// 3. Tambah total_paid ke orders (jika belum ada)
// =============================================
if (!Schema::hasColumn('orders', 'total_paid')) {
    runSql("Tambah kolom total_paid", "ALTER TABLE orders ADD COLUMN total_paid DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total_price");
} else {
    $log[] = "✅ Kolom total_paid sudah ada (skip)";
}

// =============================================
// 4. Fix payments.method - tambah ewallet
// =============================================
runSql("Convert payments.method ke VARCHAR",  "ALTER TABLE payments MODIFY COLUMN method VARCHAR(50)");
runSql("Convert payments.method ke ENUM baru","ALTER TABLE payments MODIFY COLUMN method ENUM('cash','qris','transfer','ewallet') NOT NULL DEFAULT 'cash'");

// =============================================
// 5. Fix payments.status - ganti enum
// =============================================
runSql("Convert payments.status ke VARCHAR", "ALTER TABLE payments MODIFY COLUMN status VARCHAR(50)");
runUpdate("Update confirmed → success", 'payments', ['status', 'confirmed'], ['status' => 'success']);
runUpdate("Update rejected → failed",   'payments', ['status', 'rejected'],  ['status' => 'failed']);
runSql("Convert payments.status ke ENUM baru", "ALTER TABLE payments MODIFY COLUMN status ENUM('pending','success','failed') NOT NULL DEFAULT 'pending'");

// =============================================
// 6. Tambah user_id & reference_number ke payments
// =============================================
if (!Schema::hasColumn('payments', 'user_id')) {
    runSql("Tambah kolom user_id ke payments",           "ALTER TABLE payments ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER order_id");
    runSql("Tambah kolom reference_number ke payments",  "ALTER TABLE payments ADD COLUMN reference_number VARCHAR(255) NULL AFTER method");
} else {
    $log[] = "✅ Kolom user_id sudah ada (skip)";
}

// =============================================
// 7. Tandai migration sebagai berhasil
// =============================================
try {
    $maxBatch = DB::table('migrations')->max('batch') ?? 0;
    DB::table('migrations')->insert([
        'migration' => '2026_07_07_000001_update_payments_and_orders_schema',
        'batch'     => $maxBatch + 1,
    ]);
    $log[] = "✅ Migration ditandai sebagai DONE";
} catch (\Exception $e) {
    $errors[] = "⚠️ Tandai migration → " . $e->getMessage();
}

// =============================================
// Tampilkan hasil
// =============================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>WashFlow - Fix Database</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 30px; }
        h1 { color: #4fc3f7; }
        h2 { color: #81c784; margin-top: 30px; }
        h2.err { color: #ef9a9a; }
        li { padding: 4px 0; font-size: 15px; }
        .done { background: #1b5e20; padding: 20px; border-radius: 8px; margin-top: 20px; color: #a5d6a7; font-size: 18px; font-weight: bold; }
        .warn { background: #3e2723; padding: 15px; border-radius: 8px; margin-top: 20px; color: #ffcc80; }
    </style>
</head>
<body>
    <h1>🔧 WashFlow – Fix Database</h1>

    <h2>✅ Berhasil (<?= count($log) ?> langkah):</h2>
    <ul>
        <?php foreach ($log as $l): ?>
            <li><?= htmlspecialchars($l) ?></li>
        <?php endforeach; ?>
    </ul>

    <?php if (!empty($errors)): ?>
    <h2 class="err">⚠️ Peringatan (<?= count($errors) ?> item):</h2>
    <ul>
        <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <div class="done">
        🎉 Selesai! Database sudah diperbaiki.<br>
        Sekarang coba buat pesanan lagi di aplikasi Flutter.<br><br>
        <strong style="color:#ff8a65">⚠️ HAPUS FILE INI: backend/public/fix_db.php</strong>
    </div>
</body>
</html>
