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
        $table->foreignId('user_id')->constrained();
        $table->foreignId('voucher_id')->constrained();
        $table->string('reference_number')->unique(); // Order ID untuk Midtrans
        $table->decimal('total_price', 15, 2);
        $table->string('payment_status')->default('pending'); // pending, success, expired
        $table->string('payment_url')->nullable(); // Untuk link/QR Midtrans
        $table->string('token_received')->nullable(); // Kode voucher yang didapat setelah sukses
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
