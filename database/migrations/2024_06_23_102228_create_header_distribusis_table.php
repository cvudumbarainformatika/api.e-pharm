<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHeaderDistribusisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('header_distribusis', function (Blueprint $table) {
            $table->id();
            $table->string('nodistribusi')->unique()->nullable();
            $table->string('pengirim')->nullable();
            $table->string('dari')->nullable();
            $table->string('tujuan')->nullable();
            $table->string('penerima')->nullable();
            $table->date('tgl_permintaan')->nullable();
            $table->date('tgl_distribusi')->nullable();
            $table->date('tgl_terima')->nullable();
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
        Schema::dropIfExists('header_distribusis');
    }
}
