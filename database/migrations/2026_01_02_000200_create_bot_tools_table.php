<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_tools', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        DB::table('bot_tools')->insert([
            ['name' => 'get_messages', 'label' => '過去メッセージ取得', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'get_message_by_id', 'label' => 'ルームID+メッセージIDでメッセージ取得', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'list_joined_rooms', 'label' => 'Bot参加ルーム一覧取得', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_tools');
    }
};
