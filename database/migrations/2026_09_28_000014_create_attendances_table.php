<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('period');
            $table->string('status', 20)->default('present');
            $table->string('method', 20);
            $table->foreignId('match_log_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            // 二重登録の防止（クラス別の一覧のように「生徒」から探すときもこれが使われる）
            $table->unique(['student_id', 'subject_id', 'date', 'period']);
            // 出席受付・出席一覧・人数の集計は「授業・日付・時限」で探すので、その順の索引を付ける
            $table->index(['subject_id', 'date', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};