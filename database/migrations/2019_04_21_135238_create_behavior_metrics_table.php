<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBehaviorMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('behavior_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('pageviews');
            $table->string('page_path');
            $table->date('date_retrieved');
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
        Schema::dropIfExists('behavior_metrics');
    }
}
