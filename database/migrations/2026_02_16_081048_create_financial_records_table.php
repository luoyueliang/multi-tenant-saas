<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_records', function (Blueprint $table) {
            $table->unsignedBigInteger('financial_record_id')->primary()->comment('财务记录ID（全局ID，16位数字）');
            $table->unsignedBigInteger('tenant_id')->comment('租户ID');
            $table->enum('type', ['subscription', 'recharge', 'commission', 'refund'])->comment('交易类型');
            $table->unsignedBigInteger('amount')->comment('金额（分）');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending')->comment('状态');
            $table->string('payment_method', 50)->nullable()->comment('支付方式');
            $table->string('payment_order_no', 100)->nullable()->comment('支付订单号');
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->json('metadata')->nullable()->comment('元数据');
            $table->timestamps();

            $table->index(['tenant_id', 'type', 'created_at'], 'idx_financial_records_tenant');
            $table->index('payment_order_no', 'idx_financial_records_order');
            $table->index('status', 'idx_financial_records_status');
            $table->index('created_at', 'idx_financial_records_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_records');
    }
};
