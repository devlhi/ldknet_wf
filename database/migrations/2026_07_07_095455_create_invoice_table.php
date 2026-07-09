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
        if (Schema::hasTable('invoice')) {
            return; // tabel sudah ada di DB legacy/production — jangan sentuh
        }

        Schema::create('invoice', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('code', 50);
            $table->string('idpel', 100);
            $table->string('nama', 100);
            $table->string('category', 100);
            $table->string('service', 100);
            $table->string('method', 100);
            $table->text('penerima');
            $table->string('metode_pembayaran', 200);
            $table->string('package', 100);
            $table->integer('price');
            $table->integer('random_price');
            $table->integer('received');
            $table->enum('status', ['Pending', 'Success', 'Error', 'Unpaid', 'Paid']);
            $table->string('reference', 100);
            $table->date('date');
            $table->date('expdate');
            $table->string('exppay', 50);
            $table->date('last_update');
            $table->string('payment_url')->nullable();
            $table->string('qr_url')->nullable();
            $table->text('update_by');
            $table->string('bukti_pembayaran', 500);
            $table->string('data_invoice', 100);
            $table->string('account', 100);
            $table->string('code_coupon', 100);
            $table->string('otp', 6);
            $table->string('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice');
    }
};
