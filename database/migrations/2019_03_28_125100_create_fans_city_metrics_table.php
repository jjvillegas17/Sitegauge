<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFansCityMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fans_city_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('city1');
            $table->integer('value1');
            $table->string('city2');
            $table->integer('value2');
            $table->string('city3');
            $table->integer('value3');
            $table->string('city4');
            $table->integer('value4');
            $table->string('city5');
            $table->integer('value5');
            $table->date('date_retrieved');
            $table->unsignedBigInteger('facebook_pages_id');
            $table->foreign('facebook_pages_id')->references('id')->on('facebook_pages')->onDelete('cascade');
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
        Schema::dropIfExists('fans_city_metrics');
    }
}
