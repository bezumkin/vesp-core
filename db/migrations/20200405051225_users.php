<?php

use Illuminate\Database\Schema\Blueprint;
use Vesp\Services\Migration;

class Users extends Migration
{
    public function up()
    {
        $this->schema->create(
            'user_roles',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('title')->unique();
                $table->json('scope');
                $table->timestamps();
            }
        );

        $this->schema->create(
            'users',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('username')->unique();
                $table->string('password');
                $table->integer('role_id')->unsigned();
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->foreign('role_id')
                    ->references('id')->on('user_roles')
                    ->onUpdate('restrict')
                    ->onDelete('set null');
            }
        );
    }

    public function down()
    {
        $this->schema->drop('users');
        $this->schema->drop('user_roles');
    }
}