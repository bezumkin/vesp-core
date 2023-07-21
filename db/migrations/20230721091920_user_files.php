<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Vesp\Services\Migration;

// @codingStandardsIgnoreLine
class UserFiles extends Migration
{
    public function up(): void
    {
        $this->schema->create(
            'user_files',
            function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->constrained('users')->cascadeOnDelete();
                $table->foreignId('file_id')
                    ->constrained('files')->cascadeOnDelete();
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->primary(['user_id', 'file_id']);
            }
        );
    }

    public function down(): void
    {
        $this->schema->drop('user_files');
    }
}
