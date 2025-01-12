<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('transaction_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->double('qty', 20, 2)->default(0);
            $table->boolean('racikan')->nullable();
            $table->double('nilai_r', 20, 2)->default(0);
            $table->double('harga_beli', 20, 2)->default(0);
            $table->double('harga', 20, 2)->default(0);
            $table->double('sub_total', 20, 3)->default(0);
            // $table->double('diskon', 20, 3)->default(0);
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
        Schema::dropIfExists('detail_transactions');
    }
}
