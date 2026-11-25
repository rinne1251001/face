<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            // どの年度のクラスか（クラスがある年度は削除できない）
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name', 50);
            $table->unsignedTinyInteger('grade'); // 学年
            // ホームルームの教室（決まっていなくてもよい。教室を削除したら「未設定」に戻す）
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            // 同じ年度に同じ名前のクラスは作れない（年度が違えば、毎年「1T1」を作れる）
            $table->unique(['academic_year_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};