<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class MoreMetaFieldColumns extends AbstractMigration
{
    public function change()
    {
        $metaFieldsTable = $this->table('meta_fields');

        $metaFieldsTable
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->update();

        $metaFieldsTable
            ->addIndex('created')
            ->addIndex('modified');

        $metaTemplateFieldsTable = $this->table('meta_template_fields')
            ->addColumn('counter', 'integer', [
                'default' => 0,
                'length' => 11,
                'null' => false,
                'signed' => false,
                'comment' => 'Field used by the CounterCache behaviour to count the occurence of meta_template_fields'
            ])
            ->addForeignKey('meta_template_id', 'meta_templates', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->update();
        
        $metaTemplate = $this->table('meta_templates')
            ->removeIndex(['uuid'])
            ->addIndex(['uuid', 'version'])
            ->update();
    }
}