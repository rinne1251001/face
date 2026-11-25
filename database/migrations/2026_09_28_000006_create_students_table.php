<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 生徒（年度が変わっても同じ行を使い続ける。クラスと出席番号は enrollments に持たせる）
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('furigana', 300);
            $table->string('student_number', 20)->unique();     //学籍番号
            // 学科（必須）。生徒がいる学科は削除できないようにする
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true); // false なら照合対象外
            $table->timestamps();
        });

        // 在籍（1人の生徒 × 1つの年度 で1件）
        // クラスと出席番号は、決まるまで空（null）のままにしておける
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            // 在籍がある年度は削除できない
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('grade'); // その年度の学年
            // クラス（未定なら null。クラスを削除したら「未定」に戻す）
            $table->foreignId('school_class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('attend_number', 20)->nullable(); // 出席番号（未定なら null）
            // 年度末の結果：promoted / repeated / graduated / withdrawn（年度更新をするまでは null）
            $table->string('result', 10)->nullable();
            $table->timestamps();

            // 1人の生徒は、1つの年度に1回だけ在籍する
            $table->unique(['student_id', 'academic_year_id']);
            // 同じクラスの中で出席番号は重複しない（null どうしは重複とみなされないので、未定の生徒は何人いてもよい）
            $table->unique(['school_class_id', 'attend_number']);
            // 「この年度の◯年生」を探すときに使う
            $table->index(['academic_year_id', 'grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('students');
    }
};