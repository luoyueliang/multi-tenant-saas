<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('setting_id')->primary()->comment('配置ID（全局ID，16位数字）');
            $table->string('group', 50)->comment('配置组（dify/system/mail/credit）');
            $table->string('key', 100)->comment('配置键');
            $table->text('value')->nullable()->comment('配置值（支持JSON）');
            $table->boolean('is_encrypted')->default(false)->comment('是否加密存储');
            $table->string('description', 255)->nullable()->comment('配置说明');
            $table->timestamps();

            $table->unique(['group', 'key'], 'uk_group_key');
            $table->index('group', 'idx_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
