<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFansMaleAgeMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fans_male_age_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('m_13_17');
            $table->integer('m_18_24');
            $table->integer('m_25_34');
            $table->integer('m_35_44');
            $table->integer('m_45_54');
            $table->integer('m_55_64');
            $table->integer('m_65_');
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
        Schema::dropIfExists('fans_male_age_metrics');
    }
}
