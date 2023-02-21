<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class MoreDataOnMetaFields extends AbstractMigration
{
    public $autoId = false; // turn off automatic `id` column create. We want it to be `int(10) unsigned`

    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $metaFieldTable = $this->table('meta_fields');
        if (!$metaFieldTable->hasColumn('meta_template_directory_id')) {
            $metaFieldTable
                ->addColumn('meta_template_directory_id', 'integer', [
                    'default' => null,
                    'null' => false,
                    'signed' => false,
                    'length' => 10
                ])
                ->addIndex('meta_template_directory_id')
                ->update();
        }

        $exists = $this->hasTable('meta_template_name_directory');
        if (!$exists) {
            $templateNameDirectoryTable = $this->table('meta_template_name_directory', [
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci'
            ]);
            $templateNameDirectoryTable
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'limit' => 10,
                    'signed' => false,
                ])
                ->addPrimaryKey('id')
                ->addColumn('name', 'string', [
                    'null' => false,
                    'limit' => 191,
                    'collation' => 'utf8mb4_unicode_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->addColumn('namespace', 'string', [
                    'null' => false,
                    'limit' => 191,
                    'collation' => 'utf8mb4_unicode_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->addColumn('version', 'string', [
                    'null' => false,
                    'limit' => 191,
                    'collation' => 'utf8mb4_unicode_ci',
                    'encoding' => 'utf8mb4',
                ])
                ->addColumn('uuid', 'uuid', [
                    'null' => false,
                    'default' => null,
                ]);

            $templateNameDirectoryTable
                ->addIndex(['uuid', 'version'], ['unique' => true])
                ->addIndex('name')
                ->addIndex('namespace');

            $templateNameDirectoryTable->create();

            $allTemplates = $this->getAllTemplates();
            $this->populateTemplateDirectoryTable($allTemplates);

            $metaTemplateTable = $this->table('meta_templates');
            $metaTemplateTable
                ->addColumn('meta_template_directory_id', 'integer', [
                    'default' => null,
                    'null' => false,
                    'signed' => false,
                    'length' => 10
                ])
                ->update();
            $this->assignTemplateDirectory($allTemplates);
            $metaTemplateTable
                ->addForeignKey('meta_template_directory_id', 'meta_template_name_directory', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->save();

            $metaFieldTable
                ->dropForeignKey('meta_template_id')
                ->dropForeignKey('meta_template_field_id')
                ->addForeignKey('meta_template_directory_id', 'meta_template_name_directory', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->save();
        }
    }

    private function populateTemplateDirectoryTable(array $allTemplates): void
    {
        $builder = $this->getQueryBuilder()
            ->insert(['uuid', 'name', 'namespace', 'version'])
            ->into('meta_template_name_directory');

        if (!empty($allTemplates)) {
            foreach ($allTemplates as $template) {
                $builder->values([
                    'uuid' => $template['uuid'],
                    'name' => $template['name'],
                    'namespace' => $template['namespace'],
                    'version' => $template['version'],
                ]);
            }
            $builder->execute();
        }
    }

    private function assignTemplateDirectory(array $allTemplates): void
    {
        foreach ($allTemplates as $template) {
            $directory_template = $this->getDirectoryTemplate($template['uuid'], $template['version'])[0];
            $this->getQueryBuilder()
                ->update('meta_templates')
                ->set('meta_template_directory_id', $directory_template['id'])
                ->where(['meta_template_id' => $template['id']])
                ->execute();
            $this->getQueryBuilder()
                ->update('meta_fields')
                ->set('meta_template_directory_id', $directory_template['id'])
                ->where(['id' => $template['id']])
                ->execute();
        }
    }

    private function getAllTemplates(): array
    {
        return $this->getQueryBuilder()
            ->select(['id', 'uuid', 'name', 'namespace', 'version'])
            ->from('meta_templates')
            ->execute()->fetchAll('assoc');
    }

    private function getDirectoryTemplate(string $uuid, string $version): array
    {
        return $this->getQueryBuilder()
            ->select(['id', 'uuid', 'version'])
            ->from('meta_template_name_directory')
            ->where([
                'uuid' => $uuid,
                'version' => $version,
            ])
            ->execute()->fetchAll('assoc');
    }
}
