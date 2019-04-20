<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFansFemaleAgeMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fans_female_age_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('f_13_17');
            $table->integer('f_18_24');
            $table->integer('f_25_34');
            $table->integer('f_35_44');
            $table->integer('f_45_54');
            $table->integer('f_55_64');
            $table->integer('f_65_');
            $table->date('date_retrieved');
            $table->unsignedBigInteger('facebook_page_id');
            $table->foreign('facebook_page_id')->references('id')->on('facebook_pages')->onDelete('cascade');
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
        Schema::dropIfExists('fans_female_age_metrics');
    }
}
