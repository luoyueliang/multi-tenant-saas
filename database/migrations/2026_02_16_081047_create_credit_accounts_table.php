<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_account_id')->primary()->comment('账户ID（全局ID，16位数字）');
            $table->unsignedBigInteger('tenant_id')->comment('租户ID');
            $table->unsignedBigInteger('user_id')->nullable()->comment('用户ID（NULL表示租户级账户）');
            $table->enum('account_type', ['enterprise', 'personal'])->default('personal')->comment('账户类型');
            $table->unsignedBigInteger('balance')->default(0)->comment('账户余额');
            $table->unsignedBigInteger('total_recharged')->default(0)->comment('累计充值');
            $table->unsignedBigInteger('total_consumed')->default(0)->comment('累计消费');
            $table->enum('status', ['active', 'frozen', 'closed'])->default('active')->comment('账户状态');
            $table->timestamps();

            $table->index('tenant_id', 'idx_credit_accounts_tenant');
            $table->index('user_id', 'idx_credit_accounts_user');
            $table->index(['tenant_id', 'user_id'], 'idx_tenant_user');
            $table->index(['tenant_id', 'account_type'], 'idx_tenant_account_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_accounts');
    }
};
