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
            $table->unsignedBigInteger('merk_id')->nullable();
            $table->unsignedBigInteger('satuan_id')->nunllable();
            $table->unsignedBigInteger('pengali')->nunllable();
            $table->unsignedBigInteger('satuan_besar_id')->nunllable();
            $table->double('harga_beli')->default(0);
            $table->double('harga_jual_umum')->default(0);
            $table->double('harga_jual_resep')->default(0);
            $table->double('harga_jual_cust')->default(0);
            $table->double('stok_awal')->default(0);
            $table->double('limit_stok')->default(2);
            $table->unsignedBigInteger('rak_id')->nullable();
            $table->unsignedBigInteger('kategori_id')->nullable();
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
