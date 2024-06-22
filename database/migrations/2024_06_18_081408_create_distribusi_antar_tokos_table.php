<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistribusiAntarTokosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('distribusi_antar_tokos', function (Blueprint $table) {
            $table->id();
            $table->string('reff')->unique()->default('trl70k498vdb9m4');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('pengirim')->nullable();
            $table->string('dari')->nullable();
            $table->string('tujuan')->nullable();
            $table->string('penerima')->nullable();
            $table->double('qty', 20, 2)->default(0);
            $table->date('tgl_distribusi')->nullable();
            $table->date('tgl_terima')->nullable();
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
        Schema::dropIfExists('distribusi_antar_tokos');
    }
}
