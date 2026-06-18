<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('setting_id')->primary()->comment('配置ID（全局ID，16位数字）');
            $table->unsignedBigInteger('tenant_id')->comment('租户ID');
            $table->string('group', 50)->comment('配置组（oauth/mail/info）');
            $table->string('key', 100)->comment('配置键');
            $table->text('value')->nullable()->comment('配置值（支持JSON）');
            $table->boolean('is_encrypted')->default(false)->comment('是否加密存储');
            $table->string('description', 255)->nullable()->comment('配置说明');
            $table->timestamps();

            $table->unique(['tenant_id', 'group', 'key'], 'uk_tenant_group_key');
            $table->index('tenant_id', 'idx_tenant_id');
            $table->index(['tenant_id', 'group'], 'idx_tenant_group');

            $table->foreign('tenant_id')
                ->references('tenant_id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
