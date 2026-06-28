<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->unsignedBigInteger('usage_record_id')->primary();
            $table->unsignedBigInteger('tenant_id');
            $table->string('metric_type', 50);
            $table->decimal('value', 18, 4);
            $table->string('period', 7)->comment('计费周期，格式 YYYYMM');
            $table->timestamp('recorded_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'metric_type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
