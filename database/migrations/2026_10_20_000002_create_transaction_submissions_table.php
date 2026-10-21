<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('scope', 30);
            $table->uuid('token');
            $table->timestamp('created_at');
            $table->unique(['user_id', 'scope', 'token'], 'transaction_submission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_submissions');
    }
};
