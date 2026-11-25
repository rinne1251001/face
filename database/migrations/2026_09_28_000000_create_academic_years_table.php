<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 年度（クラス・授業・在籍は、必ずどれか1つの年度に属する）
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique(); // 2027 なら「2027年度」。同じ年度は1つだけ
            $table->date('start_date');                     // 例：2027-04-01
            $table->date('end_date');                       // 例：2028-03-31
            $table->timestamp('unlocked_at')->nullable();   // 過去の年度は読み取り専用。管理者が「編集を許可」すると日時が入り、「読み取り専用に戻す」と null に戻る
            $table->timestamps();
        });

        // 学科（何年で卒業するかは、学科ごとに違う）
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->unsignedTinyInteger('years'); // 修業年限（2年制なら 2）。この学年を終えたら卒業
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
        Schema::dropIfExists('academic_years');
    }
};