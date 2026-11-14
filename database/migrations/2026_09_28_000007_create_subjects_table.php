<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 授業と時間割は「一緒に作って一緒に消す」関係なので、1ファイルにまとめている
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            // どの年度の授業か（授業がある年度は削除できない）
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->unsignedTinyInteger('grade');   // 学年
            $table->date('start_date')->nullable(); // 年度更新でコピーした直後は空。あとで登録する
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('subject_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->unsignedTinyInteger('period_start');
            $table->unsignedTinyInteger('period_end');
            // 授業をする教室（決まっていなくてもよい。教室を削除したら「未設定」に戻す）
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            // 同じ授業に同じ曜日は1つだけ。曜日での絞り込みにもこの索引が使われる
            $table->unique(['subject_id', 'day_of_week']);
            // ※ index(['day_of_week', 'period_start']) はどこからも使われていないので削除した
        });
    }

    public function down(): void
    {
        // 作った順の逆（子 → 親）に消す
        Schema::dropIfExists('subject_schedules');
        Schema::dropIfExists('subjects');
    }
};