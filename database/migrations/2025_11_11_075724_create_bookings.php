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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            
            // ✅ Tambahkan booking_code yang unique
            $table->string('booking_code', 20)->unique()->index();
            
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('nama_pemesan');
            $table->string('nomor_wa', 20);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            
            // ✅ Tambahkan jumlah dan durasi_hari yang sempat missing
            $table->integer('jumlah')->default(1);
            $table->integer('durasi_hari')->default(1);
            
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->decimal('total_harga', 12, 2); // Naikkan dari 10 ke 12 untuk harga besar
            $table->text('catatan')->nullable();
            
            // ✅ Tambahkan indexes untuk performa
            $table->index('status');
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
            $table->index('created_at');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
