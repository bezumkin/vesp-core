<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Vesp\Services\Migration;

class Files extends Migration
{
    public function up(): void
    {
        $this->schema->create(
            'files',
            static function (Blueprint $table) {
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

    public function down(): void
    {
        $this->schema->drop('files');
    }
}
