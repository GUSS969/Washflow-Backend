<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('menunggu_konfirmasi', 'diterima', 'dicuci', 'dikeringkan', 'disetrika', 'selesai', 'sudah_diambil') NOT NULL DEFAULT 'diterima'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('diterima', 'dicuci', 'dikeringkan', 'disetrika', 'selesai', 'sudah_diambil') NOT NULL DEFAULT 'diterima'");
    }
};
