<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('voucher_tokens', function (Blueprint $table) {
        $table->id();
        $table->foreignId('voucher_id')->constrained()->onDelete('cascade');
        $table->string('token_code')->unique(); // Kode unik voucher
        $table->enum('status', ['available', 'sold'])->default('available');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_tokens');
    }
};
