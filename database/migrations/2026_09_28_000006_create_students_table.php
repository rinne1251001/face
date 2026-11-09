<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('furigana', 300);
            $table->string('student_number', 20)->unique();     //学籍番号
            $table->string('attend_number', 20)->unique();     //出席番号
            // 所属クラス（必須）。生徒がいるクラスは削除できないようにする
            $table->foreignId('school_class_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true); // false なら照合対象外
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};