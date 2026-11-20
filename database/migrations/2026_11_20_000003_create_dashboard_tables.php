<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('company')->nullable();
            $table->enum('tier', ['free', 'starter', 'growth', 'enterprise'])->default('starter');
            $table->string('country')->default('Indonesia');
            $table->decimal('balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });

        Schema::create('gpu_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('hostname')->unique();
            $table->foreignId('gpu_model_id')->constrained()->cascadeOnDelete();
            $table->string('region');
            $table->unsignedTinyInteger('gpu_count');
            $table->enum('status', ['online', 'draining', 'offline', 'maintenance'])->default('online');
            $table->decimal('cpu_util', 5, 1)->default(0);
            $table->decimal('mem_util', 5, 1)->default(0);
            $table->decimal('gpu_util', 5, 1)->default(0);
            $table->decimal('temp_c', 5, 1)->default(0);
            $table->unsignedInteger('power_w')->default(0);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();
        });

        Schema::create('clusters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('region');
            $table->foreignId('gpu_model_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('node_count');
            $table->unsignedInteger('gpu_total');
            $table->enum('status', ['healthy', 'degraded', 'critical'])->default('healthy');
            $table->decimal('utilization', 5, 1)->default(0);
            $table->timestamps();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gpu_model_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['training', 'fine-tuning', 'inference', 'benchmark'])->default('training');
            $table->enum('status', ['queued', 'running', 'completed', 'failed'])->default('queued');
            $table->unsignedTinyInteger('gpu_count');
            $table->decimal('cost', 12, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('daily_revenues', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->decimal('revenue', 12, 2)->default(0);
            $table->unsignedInteger('gpu_hours')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daily_revenues');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('clusters');
        Schema::dropIfExists('gpu_nodes');
        Schema::dropIfExists('customers');
    }
};
