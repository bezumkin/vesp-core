<?php

declare(strict_types=1);

namespace Vesp\Services;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Builder;
use Phinx\Migration\AbstractMigration;

class Migration extends AbstractMigration
{
    public Manager $eloquent;
    public DatabaseManager $db;
    public Builder $schema;

    public function init(): void
    {
        $this->eloquent = new Eloquent();
        $this->db = $this->eloquent->getDatabaseManager();
        $this->schema = $this->eloquent->getConnection()->getSchemaBuilder();
    }
}
