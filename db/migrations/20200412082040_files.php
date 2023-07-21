<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Vesp\Services\Migration;

// @codingStandardsIgnoreLine
class Files extends Migration
{
    public function up(): void
    {
        $this->schema->create(
            'files',
            static function (Blueprint $table) {
                $table->id();
                $table->string('file');
                $table->string('path');
                $table->string('title')->nullable();
                $table->string('type')->nullable();
                $table->unsignedSmallInteger('width')->nullable();
                $table->unsignedSmallInteger('height')->nullable();
                $table->unsignedInteger('size')->nullable();
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
