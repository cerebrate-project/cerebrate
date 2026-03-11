<?php

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class GenerateAuthkeyCommand extends Command
{
    protected $modelClass = 'Users';

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Generate an auth key for a given user.');
        $parser->addArgument('user', [
            'help' => 'User ID (numeric) or username.',
            'required' => true,
        ]);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $userIdentifier = $args->getArgument('user');

        if (is_numeric($userIdentifier)) {
            $condition = ['id' => $userIdentifier];
        } else {
            $condition = ['username' => $userIdentifier];
        }
        $user = $this->Users->find()->where($condition)->first();

        if (empty($user)) {
            $io->error(sprintf('User "%s" not found.', $userIdentifier));
            return static::CODE_ERROR;
        }

        $this->loadModel('AuthKeys');
        $authkey = $this->AuthKeys->newEntity([
            'user_id' => $user->id,
            'comment' => 'Generated via CLI',
            'expiration' => 0,
        ]);

        if (!$this->AuthKeys->save($authkey)) {
            $io->error('Could not save the auth key.');
            return static::CODE_ERROR;
        }

        $io->out($authkey->authkey_raw);
        return static::CODE_SUCCESS;
    }
}
