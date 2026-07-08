<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify orders table to add 'menunggu_konfirmasi'
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('belum_lunas', 'menunggu_konfirmasi', 'lunas') DEFAULT 'belum_lunas'");
        
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending')->after('method');
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('belum_lunas', 'lunas') DEFAULT 'belum_lunas'");
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};