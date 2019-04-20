<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostDetailsMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_details_metrics', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('type');
            $table->string('message');
            $table->string('targeting');
            $table->string('link');
            $table->integer('comments');
            $table->integer('impressions');
            $table->integer('likes');
            $table->integer('love');
            $table->integer('haha');
            $table->integer('wow');
            $table->integer('sad');
            $table->integer('angry');
            $table->integer('engaged_users');
            $table->date('created_time');
            $table->unsignedBigInteger('facebook_page_id');
            $table->foreign('facebook_page_id')->references('id')->on('facebook_pages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('post_details_metrics');
    }
}
