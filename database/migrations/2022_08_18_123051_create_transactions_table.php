<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reff')->unique()->default('l70k498vdb9m4');
            $table->string('faktur')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('nama')->nullable();
            $table->json('pasien')->nullable();
            $table->enum('jenis', ['tunai', 'hutang', 'piutang'])->default('tunai');
            $table->double('total')->default(0);
            $table->double('ongkir')->default(0);
            $table->double('potongan')->default(0);
            $table->double('bayar')->default(0);
            $table->double('kembali')->default(0);
            $table->date('tempo')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('kasir_id')->nullable(); // kasir_id adalah user dengan role kasir
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('dokter_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
