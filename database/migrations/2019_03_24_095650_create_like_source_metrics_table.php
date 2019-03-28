<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLikeSourceMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('like_source_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('ads');
            $table->integer('news_feed');
            $table->integer('page_suggestions');
            $table->integer('restored_likes');
            $table->integer('search');
            $table->integer('your_page');
            $table->integer('other');
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
        Schema::dropIfExists('like_source_metrics');
    }
}
