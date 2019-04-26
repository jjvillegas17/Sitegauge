<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAudienceMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audience_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('sessions');
            $table->integer('users');
            $table->integer('new_users');
            $table->integer('sessions_per_user');
            $table->integer('pageviews');
            $table->integer('pageviews_per_session');
            $table->time('avg_session_duration');
            $table->float('bounce_rate', 5, 2);
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
        Schema::dropIfExists('audience_metrics');
    }
}
