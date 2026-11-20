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
        Schema::create('instance_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gpu_model_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('gpu_count');
            $table->string('region');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
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
        Schema::dropIfExists('instance_offerings');
    }
};
