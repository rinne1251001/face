<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                 // 授業名
            $table->unsignedTinyInteger('day_of_week');  // 曜日：1=月 2=火 … 7=日
            $table->unsignedTinyInteger('period_start'); // 開始時限（3限〜5限なら 3）
            $table->unsignedTinyInteger('period_end');   // 終了時限（1限だけなら開始と同じ 1）
            $table->timestamps();
            $table->index(['day_of_week', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};