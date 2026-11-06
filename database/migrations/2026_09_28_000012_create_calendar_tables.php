<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 日付ごとの例外（祝日は自動なので、ここには学校独自の休みと振替だけを入れる）
        //   day_of_week が null … その日は授業なし（休校・学校行事など）
        //   day_of_week が 1〜7 … その日は「その曜日の時間割」で授業（例：火曜日に水曜日の授業 → 3）
        //                           祝日に授業をする日も、ここに入れればその曜日の時間割で授業になる
        Schema::create('calendar_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->string('note', 100)->nullable(); // 画面に出すメモ（例：文化祭、11/3の振替）
            $table->timestamps();
        });

        // 変則授業（年に数回だけなど、曜日が決まっていない授業。日付と時限を1回ずつ登録する）
        Schema::create('special_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('period_start');
            $table->unsignedTinyInteger('period_end');
            $table->string('note', 100)->nullable();
            $table->timestamps();
            $table->unique(['subject_id', 'date']); // 同じ授業は1日に1回まで（毎週の時間割と同じ考え方）
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_classes');
        Schema::dropIfExists('calendar_days');
    }
};