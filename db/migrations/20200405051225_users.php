<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Vesp\Services\Migration;

// @codingStandardsIgnoreLine
class Users extends Migration
{
    public function up(): void
    {
        $this->schema->create(
            'user_roles',
            static function (Blueprint $table) {
                $table->id();
                $table->string('title')->unique();
                $table->json('scope');
                $table->timestamps();
            }
        );

        $this->schema->create(
            'users',
            static function (Blueprint $table) {
                $table->id();
                $table->string('username')->unique();
                $table->string('password')->nullable();
                $table->foreignId('role_id')
                    ->constrained('user_roles')->cascadeOnDelete();
                $table->boolean('active')->default(true);
                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        $this->schema->drop('users');
        $this->schema->drop('user_roles');
    }
}
