<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update orders table safely
        if (!Schema::hasColumn('orders', 'total_paid')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('total_paid', 12, 2)->default(0)->after('total_price');
            });
        }

        // Convert enum to varchar temporarily
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status VARCHAR(50)");
        
        // Update existing data
        DB::table('orders')->where('payment_status', 'belum_lunas')->update(['payment_status' => 'unpaid']);
        DB::table('orders')->where('payment_status', 'menunggu_konfirmasi')->update(['payment_status' => 'partial']);
        DB::table('orders')->where('payment_status', 'lunas')->update(['payment_status' => 'paid']);
        
        // Convert to new enum
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid'");

        // Update payments table safely
        if (!Schema::hasColumn('payments', 'user_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('order_id');
                $table->string('reference_number')->nullable()->after('method');
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Convert payment method to varchar
        DB::statement("ALTER TABLE payments MODIFY COLUMN method VARCHAR(50)");
        // Convert to new method enum
        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('cash', 'qris', 'transfer', 'ewallet')");
        
        // Convert status enum to varchar
        DB::statement("ALTER TABLE payments MODIFY COLUMN status VARCHAR(50)");
        // Update existing status data
        DB::table('payments')->where('status', 'confirmed')->update(['status' => 'success']);
        DB::table('payments')->where('status', 'rejected')->update(['status' => 'failed']);
        // Convert to new status enum
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'success', 'failed') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('belum_lunas', 'menunggu_konfirmasi', 'lunas') DEFAULT 'belum_lunas'");
        
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('total_paid');
        });

        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('cash', 'qris', 'transfer')");
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending'");

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'reference_number']);
        });
    }
};
