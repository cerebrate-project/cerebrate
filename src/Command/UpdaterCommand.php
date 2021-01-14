<?php
namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Cake\Database\Schema\TableSchema;
use Cake\Utility\Security;

class UpdaterCommand extends Command
{
    protected $modelClass = 'Users';
    protected $availableUpdates = [
        'meta-templates-v2' => 'metaTemplateV2',
    ];

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Execute updates.');
        $parser->addArgument('updateName', [
            'help' => 'The name of the update to execute',
            'required' => false,
            'choices' => array_keys($this->availableUpdates)
        ]);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->io = $io;
        $targetUpdateName = $args->getArgument('updateName');

        if (!in_array($targetUpdateName, array_keys($this->availableUpdates))) {
            $io->out('Available updates:');
            $io->helper('Table')->output($this->listAvailableUpdates());
            die(1);
        }

        $selection = $io->askChoice("Do you wish to apply update `{$targetUpdateName}`?", ['Y', 'N'], 'N');
        if ($selection == 'Y') {
            $updateFunction = $this->availableUpdates[$targetUpdateName];
            $updateResult = $this->{$updateFunction}();
        } else {
            $io->out('Update canceled');
        }
    }

    private function listAvailableUpdates()
    {
        $list = [['Update name']];
        foreach ($this->availableUpdates as $updateName => $f) {
            $list[] = [$updateName];
        }
        return $list;
    }

    private function metaTemplateV2()
    {
        
        $db = ConnectionManager::get('default');
        try {
            $db->query("ALTER TABLE `meta_fields` ADD `meta_template_id` int(10) unsigned NOT NULL;");
        } catch (\Exception $e) {
            $this->io->out('Caught exception: '.  $e->getMessage());
        }
        try {
            $db->query("ALTER TABLE `meta_fields` ADD `meta_template_field_id` int(10) unsigned NOT NULL;");
        } catch (\Exception $e) {
            $this->io->out('Caught exception: '.  $e->getMessage());
        }
        try {
            $db->query("ALTER TABLE `meta_templates` ADD `is_default` tinyint(1) NOT NULL DEFAULT 0;");
        } catch (\Exception $e) {
            $this->io->out('Caught exception: '.  $e->getMessage());
        }
        try {
            $db->query("ALTER TABLE `meta_fields` ADD INDEX `meta_template_id` (`meta_template_id`);");
        } catch (\Exception $e) {
            $this->io->out('Caught exception: '.  $e->getMessage());
        }
        try {
            $db->query("ALTER TABLE `meta_fields` ADD INDEX `meta_template_field_id` (`meta_template_field_id`);");
        } catch (\Exception $e) {
            $this->io->out('Caught exception: '.  $e->getMessage());
        }

        // $schemaMetaFields = new TableSchema('meta_fields');
        // $schemaMetaTemplates = new TableSchema('meta_templates');

        // $schemaMetaFields->addColumn('meta_template_id', [
        //     'type' => 'integer',
        //     'length' => 10,
        //     'unsigned' => true,
        //     'null' => false
        // ])
        // ->addColumn('meta_template_field_id', [
        //     'type' => 'integer',
        //     'length' => 10,
        //     'unsigned' => true,
        //     'null' => false
        // ])
        // ->addIndex('meta_template_id', [
        //     'columns' => ['meta_template_id'],
        //     'type' => 'index'
        // ])
        // ->addIndex('meta_template_field_id', [
        //     'columns' => ['meta_template_field_id'],
        //     'type' => 'index'
        // ]);

        
        // $schemaMetaTemplates->addColumn('is_default', [
        //     'type' => 'tinyint',
        //     'length' => 1,
        //     'null' => false,
        //     'default' => 1
        // ]);

        // $queries = $schemaMetaFields->createSql($db);

        // $collection = $db->getSchemaCollection();
        // $tableSchema = $collection->describe('meta_fields');
        // $tableSchema->addColumn('foobar', [
        //     'type' => 'integer',
        //     'length' => 10,
        //     'unsigned' => true,
        //     'null' => false
        // ]);
        return true;
    }
}