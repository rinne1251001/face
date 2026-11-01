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
            $table->foreignId('subject_id')->constrained()->restrictOnDelete(); // 出席記録がある授業は消せない
            $table->date('date');                                  // 授業日
            $table->string('status', 20)->default('present');      // present（出席）/ late（遅刻）/ absent（欠席）
            $table->string('method', 20);                          // face（顔認証）/ manual（教師が手動で登録）
            $table->foreignId('match_log_id')->nullable()->constrained()->nullOnDelete(); // 顔認証のときの照合記録
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();   // 手動で登録した教師
            $table->timestamps();
            $table->unique(['student_id', 'subject_id', 'date']); // 同じ日の同じ授業に二重登録しない
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};