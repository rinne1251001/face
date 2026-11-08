<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 教室に置きっぱなしにするカメラ（パソコン）
        // 教室を選んでパスワードを入れるとログインでき、ログインしたまま顔の照合に使う
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            // どの教室のカメラか（1つの教室に1台。教室を削除したらカメラも削除）
            $table->foreignId('classroom_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('password');  // ハッシュ化して保存する（モデルの casts で自動）
            $table->rememberToken();     // 「ログインしたままにする」ために使う
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};