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
            $table->string('nota')->nullable();
            $table->string('faktur')->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->string('nama')->nullable();
            $table->text('pasien')->nullable();
            $table->enum('jenis', ['tunai', 'hutang', 'piutang', 'non-tunai'])->default('tunai');
            $table->double('total', 20, 2)->default(0);
            $table->double('totalSemua', 20, 2)->default(0);
            $table->double('ongkir', 20, 2)->default(0);
            $table->double('potongan', 10, 2)->default(0);
            $table->double('bayar', 20, 2)->default(0);
            $table->double('kembali', 20, 2)->default(0);
            $table->double('embalase', 20, 2)->default(0);
            $table->date('tempo')->nullable();
            $table->date('tanggal_faktur')->nullable();
            $table->date('tanggal_bayar')->nullable();
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
