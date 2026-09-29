<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete(); // 最も近かった人物
            $table->float('similarity')->nullable();
            $table->float('margin')->nullable();
            $table->boolean('accepted');
            $table->string('model_version', 50);
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_logs');
    }
};