<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('image_path')->nullable();   // storage/app/private からの相対パス
            $table->json('embedding');                  // 正規化済みの特徴量
            $table->unsignedSmallInteger('dimension');
            $table->string('model_version', 50);
            $table->timestamps();
            $table->index(['model_version', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_samples');
    }
};