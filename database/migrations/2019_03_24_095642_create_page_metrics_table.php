<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePageMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // likes,views,impressions,engagements,top 5 posts, negative feedback, fans online
        // like source, new likes, content activity, content activity by type

        // fans online, like source, content act by type
        Schema::create('page_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('likes')->nullable();
            $table->integer('views')->nullable();
            $table->integer('impressions')->nullable();
            $table->integer('engagements')->nullable();
            $table->integer('posts_engagements')->nullable();
            $table->integer('content_activity')->nullable();
            $table->integer('negative_feedback')->nullable();
            $table->integer('new_likes')->nullable();
            $table->integer('video_views')->nullable();
            $table->date('date_retrieved');
            $table->unsignedBigInteger('uploader_id')->nullable();  // userId of the uploader
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
        Schema::dropIfExists('page_metrics');
    }
}
