-- ============================================================
-- JALANKAN SCRIPT INI DI phpMyAdmin atau MySQL Workbench
-- Database: washflow_db
-- ============================================================

USE washflow_db;

-- Hapus log migrasi yang gagal agar bisa diulang
DELETE FROM migrations WHERE migration = '2026_07_07_000001_update_payments_and_orders_schema';

-- ============================================================
-- LANGKAH 1: Fix kolom payment_status di tabel orders
-- ============================================================

-- Ubah dulu ke VARCHAR (bebas isi nilai apapun)
ALTER TABLE orders MODIFY COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'unpaid';

-- Update semua data lama ke nilai baru
UPDATE orders SET payment_status = 'unpaid'   WHERE payment_status = 'belum_lunas';
UPDATE orders SET payment_status = 'paid'     WHERE payment_status = 'lunas';
UPDATE orders SET payment_status = 'partial'  WHERE payment_status = 'menunggu_konfirmasi';

-- Pastikan tidak ada nilai aneh yang tersisa
UPDATE orders SET payment_status = 'unpaid' WHERE payment_status NOT IN ('unpaid', 'partial', 'paid');

-- Ubah ke ENUM baru
ALTER TABLE orders MODIFY COLUMN payment_status ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid';

-- ============================================================
-- LANGKAH 2: Tambah kolom total_paid ke tabel orders (jika belum ada)
-- ============================================================

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA='washflow_db' AND TABLE_NAME='orders' AND COLUMN_NAME='total_paid';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN total_paid DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total_price',
    'SELECT "Column total_paid already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- LANGKAH 3: Fix tabel payments - method enum
-- ============================================================

-- Ubah method ke VARCHAR dulu
ALTER TABLE payments MODIFY COLUMN method VARCHAR(50);

-- Update nilai lama jika ada
UPDATE payments SET method = 'cash'     WHERE method NOT IN ('cash', 'qris', 'transfer', 'ewallet');

-- Ubah ke ENUM baru (termasuk ewallet)
ALTER TABLE payments MODIFY COLUMN method ENUM('cash', 'qris', 'transfer', 'ewallet') NOT NULL DEFAULT 'cash';

-- ============================================================
-- LANGKAH 4: Fix tabel payments - status enum  
-- ============================================================

-- Ubah status ke VARCHAR dulu
ALTER TABLE payments MODIFY COLUMN status VARCHAR(50);

-- Update nilai lama
UPDATE payments SET status = 'success' WHERE status = 'confirmed';
UPDATE payments SET status = 'failed'  WHERE status = 'rejected';
UPDATE payments SET status = 'pending' WHERE status NOT IN ('pending', 'success', 'failed');

-- Ubah ke ENUM baru
ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'pending';

-- ============================================================
-- LANGKAH 5: Tambah kolom user_id & reference_number ke payments (jika belum ada)
-- ============================================================

SET @uid_exists = 0;
SELECT COUNT(*) INTO @uid_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA='washflow_db' AND TABLE_NAME='payments' AND COLUMN_NAME='user_id';

SET @sql2 = IF(@uid_exists = 0, 
    'ALTER TABLE payments ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER order_id',
    'SELECT "Column user_id already exists"'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET @ref_exists = 0;
SELECT COUNT(*) INTO @ref_exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA='washflow_db' AND TABLE_NAME='payments' AND COLUMN_NAME='reference_number';

SET @sql3 = IF(@ref_exists = 0, 
    'ALTER TABLE payments ADD COLUMN reference_number VARCHAR(255) NULL AFTER method',
    'SELECT "Column reference_number already exists"'
);
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- ============================================================
-- SELESAI! Tandai migration sebagai berhasil
-- ============================================================
INSERT INTO migrations (migration, batch) 
SELECT '2026_07_07_000001_update_payments_and_orders_schema', MAX(batch)+1 FROM migrations;

-- Verifikasi hasil
SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA='washflow_db' AND TABLE_NAME IN ('orders', 'payments')
AND COLUMN_NAME IN ('payment_status', 'status', 'method', 'total_paid', 'user_id', 'reference_number')
ORDER BY TABLE_NAME, COLUMN_NAME;
