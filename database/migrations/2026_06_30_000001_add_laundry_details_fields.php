<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('service_type', ['cuci_setrika', 'cuci_saja', 'setrika_saja'])->default('cuci_setrika')->after('status');
            $table->enum('perfume_type', ['tanpa_parfum', 'reguler', 'antibakteri', 'lavender', 'floral', 'sport'])->default('reguler')->after('service_type');
            $table->text('cloth_notes')->nullable()->after('perfume_type');
            $table->datetime('estimated_ready')->nullable()->after('cloth_notes');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('payment_date');
            $table->text('payment_notes')->nullable()->after('payment_proof');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['service_type', 'perfume_type', 'cloth_notes', 'estimated_ready']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_proof', 'payment_notes']);
        });
    }
};