<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTweetMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tweet_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tweet_id');
            $table->string('link');
            $table->text('text');
            $table->dateTime('created_date');
            $table->integer('impressions');
            $table->integer('engagements');
            $table->decimal('engagement_rate', 5, 4);
            $table->integer('retweets');
            $table->integer('replies');
            $table->integer('likes');
            $table->integer('follows');
            $table->integer('user_profile_clicks');
            $table->integer('media_views');
            $table->integer('media_engagements');
            $table->unsignedBigInteger('uploader_id')->nullable(); // userId of the uploader
            $table->string('twitter_id');
            $table->foreign('twitter_id')->references('id')->on('twitter_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tweet_metrics');
    }
}
