<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAcquisitionMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acquisition_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date_retrieved');
            $table->integer('direct');
            $table->integer('organic_search');
            $table->integer('social');
            $table->integer('other');
            $table->integer('referral');
            $table->string('profile_id');
            $table->foreign('profile_id')->references('profile_id')->on('google_analytics')->onDelete('cascade');
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
        Schema::dropIfExists('acquisition_metrics');
    }
}
