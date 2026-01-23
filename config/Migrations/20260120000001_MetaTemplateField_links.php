<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class MetaTemplateFieldLinks extends AbstractMigration
{
    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`

    public function change(): void
    {
        $this->table('meta_template_fields')
            ->addColumn('link', 'text', [
                'null' => true,
                'default' => null,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->save();
    }
}
