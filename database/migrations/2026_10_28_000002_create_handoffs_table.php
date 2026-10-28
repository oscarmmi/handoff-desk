<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handoffs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chat_turn_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('queue');
            $table->text('summary');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handoffs');
    }
};
