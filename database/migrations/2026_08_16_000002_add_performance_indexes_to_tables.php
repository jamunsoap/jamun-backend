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
        Schema::table('products', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('category');
            $table->index(['is_active', 'is_featured']);
            $table->index(['is_active', 'category']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('order_status');
            $table->index('payment_method');
            $table->index('created_at');
            $table->index(['customer_phone', 'created_at']);
            $table->index(['order_status', 'created_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index('is_approved');
            $table->index(['product_id', 'is_approved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['is_featured']);
            $table->dropIndex(['category']);
            $table->dropIndex(['is_active', 'is_featured']);
            $table->dropIndex(['is_active', 'category']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['order_status']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['customer_phone', 'created_at']);
            $table->dropIndex(['order_status', 'created_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['is_approved']);
            $table->dropIndex(['product_id', 'is_approved']);
        });
    }
};
