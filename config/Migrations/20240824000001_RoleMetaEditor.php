<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class RoleMetaEditor extends AbstractMigration
{
    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`

    public function change(): void
    {
        $exists = $this->table('roles')->hasColumn('perm_meta_field_editor');
        if (!$exists) {
            $this->table('roles')
                ->addColumn('perm_meta_field_editor', 'boolean', [
                    'default' => 0,
                    'null' => false,
                ])
                ->addIndex('perm_meta_field_editor')
                ->update();
        }
        $builder = $this->getQueryBuilder();
        $builder
            ->update('roles')
            ->set('perm_meta_field_editor', true)
            ->where(['perm_admin' => true])
            ->execute();
    }
}
