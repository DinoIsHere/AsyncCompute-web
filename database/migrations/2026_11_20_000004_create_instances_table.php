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
        Schema::create('instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gpu_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offering_id')->constrained('instance_offerings')->cascadeOnDelete();
            $table->string('name');
            $table->enum('status', ['provisioning', 'running', 'stopped', 'terminated'])->default('provisioning');
            $table->string('region');
            $table->unsignedTinyInteger('gpu_count');
            $table->decimal('price_per_gpu_hour', 8, 2);
            $table->string('public_ip', 45)->nullable();
            $table->string('ssh_key_name')->nullable();
            $table->timestamp('launched_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
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
        Schema::dropIfExists('instances');
    }
};
