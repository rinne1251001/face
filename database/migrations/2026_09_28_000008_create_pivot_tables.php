<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 教師が受け持つクラス（0個以上）
        Schema::create('school_class_teacher', function (Blueprint $table) {
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->primary(['school_class_id', 'teacher_id']);
        });

        // 生徒が受講する授業（1つ以上）
        Schema::create('student_subject', function (Blueprint $table) {
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->primary(['student_id', 'subject_id']);
        });

        // 教師が担当する授業（1つ以上）
        Schema::create('subject_teacher', function (Blueprint $table) {
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->primary(['subject_id', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_teacher');
        Schema::dropIfExists('student_subject');
        Schema::dropIfExists('school_class_teacher');
    }
};