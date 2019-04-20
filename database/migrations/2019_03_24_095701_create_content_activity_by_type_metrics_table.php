<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContentActivityByTypeMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_activity_by_type_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('checkin');
            $table->integer('coupon');
            $table->integer('event');
            $table->integer('fan');
            $table->integer('mention');
            $table->integer('page_post');
            $table->integer('question');
            $table->integer('user_post');
            $table->integer('other');
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
        Schema::dropIfExists('content_activity_by_type_metrics');
    }
}
