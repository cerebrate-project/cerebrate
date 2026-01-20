<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class GinormousTags extends AbstractMigration
{
    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`

    public function change(): void
    {
        $this->table('tags')
        ->removeIndex('name')
        ->save();
        $this->table('tags')
        ->changeColumn('name', 'string', [
            'limit' => 255
        ])->update();
        $this->table('tags')
        ->addIndex('name', ['limit' => 191])
        ->save();
    }
}
