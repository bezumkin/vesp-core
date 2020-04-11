<?php

use Illuminate\Database\Schema\Blueprint;
use Vesp\Services\Migration;

class Files extends Migration
{
    public function up()
    {
        $this->schema->create(
            'files',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('file');
                $table->string('path');
                $table->string('title')->nullable();
                $table->string('type')->nullable();
                $table->smallInteger('width')->unsigned()->nullable();
                $table->smallInteger('height')->unsigned()->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            }
        );
    }

    public function down()
    {
        $this->schema->drop('files');
    }
}