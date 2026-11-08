<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 教室（例：301教室・3階）
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // 教室名（同じ名前の教室は作れない）
            $table->tinyInteger('floor');          // 何階か（地下1階なら -1）
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};