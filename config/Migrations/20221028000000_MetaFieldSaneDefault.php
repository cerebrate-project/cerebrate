<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;


class MetaFieldSaneDefault extends AbstractMigration
{

    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`


    public function change()
    {
        $exists = $this->table('meta_template_fields')->hasColumn('sane_default');
        if (!$exists) {
            $this->table('meta_template_fields')
                ->addColumn('sane_default', 'text', [
                    'default' => null,
                    'null' => true,
                    'limit' => MysqlAdapter::TEXT_LONG,
                    'comment' => 'List of sane default values to be proposed',
                ])
                ->addColumn('values_list', 'text', [
                    'default' => null,
                    'null' => true,
                    'limit' => MysqlAdapter::TEXT_LONG,
                    'comment' => 'List of values that have to be used',
                ])
                ->update();
        }
    }
}
