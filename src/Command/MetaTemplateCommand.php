<?php

namespace App\Command;

use Cake\Console\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Filesystem\File;
use Cake\Utility\Hash;
use Cake\Core\Configure;

class MetaTemplateCommand extends Command
{
    protected $modelClass = 'MetaTemplates';

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Load and enable the provided meta-template');
        $parser->addArgument('uuid', [
            'help' => 'The UUID of the meta-template to load and enable',
            'required' => true
        ]);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->io = $io;
        $template_uuid = $args->getArgument('uuid');
        $metaTemplateTable = $this->modelClass;
        $this->loadModel($metaTemplateTable);
        $result = $this->MetaTemplates->createNewTemplate($template_uuid);
        if (empty($result['success'])) {
            $this->io->error(__('Could not create meta-template'));
            $this->io->error(json_encode($result));
            die(1);
        }
        $template = $this->MetaTemplates->find()->where(['uuid' => $template_uuid])->first();
        if (!empty($template)) {
            $template->enabled = true;
            $success = $this->MetaTemplates->save($template);
            if (!empty($success)) {
                $this->io->success(__('Meta-template loaded and enabled'));
            }
        }
    }
}
