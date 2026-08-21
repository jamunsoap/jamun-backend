<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'courier_name')) {
                $table->string('courier_name')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('orders', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('courier_name');
            }
            if (!Schema::hasColumn('orders', 'awb_code')) {
                $table->string('awb_code')->nullable()->after('tracking_number');
            }
            if (!Schema::hasColumn('orders', 'shiprocket_order_id')) {
                $table->string('shiprocket_order_id')->nullable()->after('awb_code');
            }
            if (!Schema::hasColumn('orders', 'shiprocket_shipment_id')) {
                $table->string('shiprocket_shipment_id')->nullable()->after('shiprocket_order_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'courier_name',
                'tracking_number',
                'awb_code',
                'shiprocket_order_id',
                'shiprocket_shipment_id',
            ]);
        });
    }
};
