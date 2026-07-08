<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Step 1: Ubah data lama dengan string UPDATE langsung (tanpa eloquent)
DB::unprepared("UPDATE orders SET payment_status = 'paid' WHERE payment_status = 'paid'"); // no-op
DB::unprepared("UPDATE orders SET payment_status = 'partial' WHERE payment_status = 'partial'"); // no-op

// Step 2: Ganti enum ke 4 nilai yang mencakup keduanya dulu
DB::unprepared("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('unpaid','partial','paid','lunas','belum_lunas') NOT NULL DEFAULT 'belum_lunas'");
echo "Step 1: Expanded enum\n";

// Step 3: Konversi nilai
DB::unprepared("UPDATE orders SET payment_status = 'lunas' WHERE payment_status = 'paid'");
DB::unprepared("UPDATE orders SET payment_status = 'belum_lunas' WHERE payment_status = 'partial' OR payment_status = 'unpaid'");
echo "Step 2: Converted values\n";

// Step 4: Set lunas untuk yang sudah lunas berdasarkan total
DB::unprepared("UPDATE orders SET payment_status = 'lunas' WHERE total_paid >= total_price AND total_price > 0");
echo "Step 3: Fixed based on total_paid\n";

// Step 5: Ubah enum ke nilai final
DB::unprepared("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('lunas','belum_lunas') NOT NULL DEFAULT 'belum_lunas'");
echo "Step 4: Final enum set\n";

// Verify
$col = DB::select("SHOW COLUMNS FROM orders LIKE 'payment_status'");
echo "Final column type: " . $col[0]->Type . "\n";

$counts = DB::select("SELECT payment_status, COUNT(*) as cnt FROM orders GROUP BY payment_status");
foreach ($counts as $c) {
    echo "  - {$c->payment_status}: {$c->cnt}\n";
}
echo "DONE!\n";
