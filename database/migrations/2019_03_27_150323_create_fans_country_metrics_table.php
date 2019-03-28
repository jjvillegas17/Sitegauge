<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFansCountryMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fans_country_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('country1');
            $table->integer('value1');
            $table->string('country2');
            $table->integer('value2');
            $table->string('country3');
            $table->integer('value3');
            $table->string('country4');
            $table->integer('value4');
            $table->string('country5');
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
        Schema::dropIfExists('fans_country_metrics');
    }
}
