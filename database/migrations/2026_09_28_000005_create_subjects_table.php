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
            $table->date('start_date')->nullable();      // 開始日（例：4月1日）
            $table->date('end_date')->nullable();        // 終了日（例：7月31日）
            $table->timestamps();
        });

        // 授業を行う曜日と時限（1つの授業が週に何回あってもよい。例：月曜1限・水曜3〜5限）
        // 受講する生徒・担当の教師・出席記録は授業（subjects）につくので、曜日が違っても同じ授業として扱える
        Schema::create('subject_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');  // 曜日：1=月 2=火 … 7=日
            $table->unsignedTinyInteger('period_start'); // 開始時限（3限〜5限なら 3）
            $table->unsignedTinyInteger('period_end');   // 終了時限（1限だけなら開始と同じ 1）
            $table->timestamps();
            $table->unique(['subject_id', 'day_of_week']); // 同じ授業は1つの曜日に1回まで
            $table->index(['day_of_week', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_schedules');
        Schema::dropIfExists('subjects');
    }
};