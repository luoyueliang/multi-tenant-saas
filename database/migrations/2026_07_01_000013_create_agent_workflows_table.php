<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_workflows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->comment('租户ID');
            $table->unsignedBigInteger('agent_id')->comment('Agent ID');
            $table->unsignedBigInteger('workflow_id')->comment('Workflow ID');
            $table->boolean('is_primary')->default(false)->comment('是否主工作流');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->unique(['agent_id', 'workflow_id']);
            $table->index(['tenant_id', 'agent_id']);
            $table->foreign('tenant_id')->references('tenant_id')->on('tenants')->onDelete('cascade');
            $table->foreign('agent_id')->references('agent_id')->on('agents')->onDelete('cascade');
            $table->foreign('workflow_id')->references('workflow_id')->on('workflows')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_workflows');
    }
};
