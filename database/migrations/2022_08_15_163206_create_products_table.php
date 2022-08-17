<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('barcode')->unique()->nullable();
            $table->string('nama')->nullable();
            $table->string('merk')->nullable();
            $table->unsignedBigInteger('satuan_id')->nunllable();
            $table->double('harga_beli')->nullable();
            $table->double('harga_jual_umum')->nullable();
            $table->double('harga_jual_resep')->nullable();
            $table->double('harga_jual_cust')->nullable();
            $table->double('stok_awal')->nullable();
            $table->unsignedBigInteger('rak_id')->nullable();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->date('expired')->nullable();
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
        Schema::dropIfExists('products');
    }
}
