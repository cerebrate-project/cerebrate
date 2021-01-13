<?php
namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
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
            $io->out('Update ' . ($updateResult ? 'successful' : 'fail'));
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
        return true;
    }
}