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
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->string('external_id')->unique(); // Order ID untuk xendit
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('voucher_id')->constrained()->onDelete('cascade');
        
        // voucher_token_id boleh NULL dulu karena token baru dikasih SETELAH bayar
        $table->foreignId('voucher_token_id')->nullable()->constrained('voucher_tokens');
        
        $table->integer('total_price');
        $table->string('snap_token')->nullable(); // Token untuk buka popup Midtrans
        $table->enum('status', ['PENDING', 'PAID', 'FAILED', 'EXPIRED'])->default('PENDING');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
