<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRedcapUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redcap_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id');
            $table->string('redcap_user_id');
            $table->string('name');
            $table->string('email');
            $table->string('redcap_expired_date')->nullable();
            $table->string('expired_date')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['project_id', 'redcap_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('redcap_users');
    }
}
