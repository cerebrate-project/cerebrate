<?php
declare(strict_types=1);

use Migrations\AbstractMigration;


class RolesPermSync extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('roles')
            ->addColumn('perm_sync', 'boolean', [
                'default' => 0,
                'null' => false,
            ])
            ->update();
    }
}