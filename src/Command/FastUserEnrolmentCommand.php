<?php

namespace App\Command;

use Cake\Console\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Filesystem\File;
use Cake\Utility\Hash;
use Cake\Core\Configure;
use Cake\Utility\Security;

class FastUserEnrolmentCommand extends Command
{
    protected $modelClass = 'Alignments';
    private $autoYes = false;
    private $alignment_type = null;
    private $individual_email_column = false;
    private $organisation_name_column = false;
    private $create_user = false;
    private $role_id = null;

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Create alignements (and optionally enroll users) based on the provided CSV file.');
        $parser->addArgument('path', [
            'help' => 'A path to the source file that should be used to create the alignments.',
            'required' => true
        ]);
        $parser->addOption('alignment_type', [
            'short' => 't',
            'help' => 'The alignment type to use',
            'default' => 'member',
        ]);
        $parser->addOption('individual_email_column', [
            'short' => 'i',
            'help' => 'The name of the column to find the individual email address',
            'default' => 'Email',
        ]);
        $parser->addOption('organisation_name_column', [
            'short' => 'o',
            'help' => 'The name of the column to find the organisation name',
            'default' => 'TeamName',
        ]);
        $parser->addOption('create_user', [
            'short' => 'c',
            'help' => 'Should the user be created',
            'boolean' => true,
            'default' => false,
        ]);
        $parser->addOption('role_id', [
            'short' => 'r',
            'help' => 'The role to assign to the user',
        ]);
        $parser->addOption('yes', [
            'short' => 'y',
            'help' => 'Automatically assume yes to any prompts',
            'default' => false,
            'boolean' => true
        ]);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->io = $io;
        $path = $args->getArgument('path');
        $this->alignment_type = $args->getOption('alignment_type');
        $this->individual_email_column = $args->getOption('individual_email_column');
        $this->organisation_name_column = $args->getOption('organisation_name_column');
        $this->create_user = $args->getOption('create_user');
        $this->role_id = $args->getOption('role_id');
        $this->autoYes = $args->getOption('yes');

        $alignmentTable = $this->modelClass;
        $this->loadModel($alignmentTable);
        $data = $this->getDataFromFile($path);
        $updatedData = $this->updateBeforeSave($data);
        $alignmentEntities = $this->marshalData($this->{$alignmentTable}, $updatedData);
        $alignmentEntitiesSample = array_slice($alignmentEntities, 0, min(5, count($alignmentEntities)));
        $ioTable = $this->transformEntitiesIntoTable($alignmentEntitiesSample);
        
        if ($this->autoYes)  {
            $this->saveAligmentData($this->{$alignmentTable}, $alignmentEntities);
        } else {
            $io->helper('Table')->output($ioTable);
            $selection = $io->askChoice('A sample of the data you about to be saved is provided above. Would you like to proceed?', ['Y', 'N'], 'N');
            if ($selection == 'Y') {
                $this->saveAligmentData($this->{$alignmentTabble}, $alignmentEntities);
            }
        }

        if ($this->create_user) {
            $this->loadModel('Users');
            if (is_null($this->role_id)) {
                $defaultRole = $this->Users->Roles->find()->select(['id'])->where(['is_default' => true])->first();
                if (empty($defaultRole)) {
                    $this->io->error(__('No default role available. Create a default role or provide the role ID to be assigned.'));
                    die(1);
                }
                $defaultRole = $defaultRole->toArray();
                if (!empty($defaultRole['perm_community_admin'])) {
                    $selection = $io->askChoice('The default role has the `admin` permission. Confirm giving the admin permission to users to be enrolled.', ['Y', 'N'], 'N');
                    if ($selection != 'Y') {
                        die(1);
                    }
                }
                if (!empty($defaultRole['perm_community_admin'])) {
                    $selection = $io->askChoice('The default role has the `community_admin` permission. Confirm giving the admin permission to users to be enrolled.', ['Y', 'N'], 'N');
                    if ($selection != 'Y') {
                        die(1);
                    }
                }
                $this->role_id = $defaultRole['id'];
            } else {
                $role = $this->Users->Roles->find()->select(['id'])->where(['id' => $this->role_id])->first();
                if (empty($role)) {
                    $this->io->error(__('Provided role ID does not exist'));
                    die(1);
                }
            }
            $userEntities = $this->createEntitiesForUsers($alignmentEntities);
            if ($this->autoYes)  {
                $this->enrolUsers($userEntities);
            } else {
                $userEntitiesSample = array_slice($userEntities, 0, min(5, count($userEntities)));
                $ioTable = $this->transformEntitiesIntoTable($userEntitiesSample);
                $io->helper('Table')->output($ioTable);
                $selection = $io->askChoice('A sample of the data you about to be saved is provided above. Would you like to proceed?', ['Y', 'N'], 'N');
                if ($selection == 'Y') {
                    $this->enrolUsers($userEntities);
                }
            }
        }
    }

    private function saveAligmentData($alignmentTable, $entities)
    {
        $this->io->verbose('Saving data');
        $saveResult = $alignmentTable->saveMany($entities);
        if ($saveResult === false) {
            $errors = [];
            $errorCount = 0;
            foreach ($entities as $entity) {
                $errorCount += 1;
                $errors[json_encode($entity->getErrors())] = true;
            }
            $this->io->error(__('{0} Errors while saving data', $errorCount));
            $this->io->error(json_encode(array_keys($errors)));
            $this->io->success(__('Saved {0} aligments', count($entities) - $errorCount));
        }
    }

    private function enrolUsers($entities)
    {
        $this->io->verbose('Saving data');
        $errors = [];
        $errorCount = 0;
        foreach ($entities as $entity) {
            $succes = $this->Users->save($entity);
            if (empty($succes)) {
                $errorCount += 1;
                $errors[json_encode($entity->getErrors())] = true;
            } else {
                if (Configure::read('keycloak.enabled')) {
                    $this->Users->enrollUserRouter($succes);
                }
            }
        }
        if (!empty($errors)) {
            $this->io->error(__('{0} Errors while saving data', $errorCount));
            $this->io->error(json_encode(array_keys($errors)));
        }
        $this->io->success(__('Enrolled {0} users', count($entities) - $errorCount));
    }

    private function createEntitiesForUsers($alignmentEntities)
    {
        $entities = [];
        foreach ($alignmentEntities as $alignmentEntity) {
            $entity = $this->Users->newEntity([
                'individual_id' => $alignmentEntity->individual_id,
                'organisation_id' => $alignmentEntity->organisation_id,
                'username' => $alignmentEntity->individual_email,
                'password' => Security::randomString(20),
                'role_id' => $this->role_id,
            ]);
            $entities[] = $entity;
        }
        return $entities;
    }

    private function marshalData($alignmentTable, $data)
    {
        $entities = $alignmentTable->newEntities($data, [
            'accessibleFields' => ($alignmentTable->newEmptyEntity())->getAccessibleFieldForNew()
        ]);
        return $entities;
    }

    private function updateBeforeSave($data)
    {
        $this->loadModel('Individuals');
        $this->loadModel('Organisations');
        $updatedData = [];
        foreach ($data as $entry) {
            $individual = $this->getIndividualByEmail($entry[$this->individual_email_column]);
            $organisation = $this->getOrganisationsByName($entry[$this->organisation_name_column]);
            if (empty($organisation)) {
                $this->io->error("Error while parsing source data. Could not find organisation with name: " . $entry[$this->organisation_name_column]);
                die(1);
            }
            if (empty($individual)) {
                $this->io->error("Error while parsing source data. Could not find individuals with email: " . $entry[$this->individual_email_column]);
                die(1);
            }
            $new = [
                'individual_id' => $individual->id,
                'organisation_id' => $organisation->id,
                'type' => $this->alignment_type,
                'individual_email' => $entry[$this->individual_email_column],
            ];
            $updatedData[] = $new;
        }
        return $updatedData;
    }

    private function getIndividualByEmail($email)
    {
        return $this->Individuals->find()->where([
            'email' => $email,
        ])->first();
    }

    private function getOrganisationsByName($name)
    {
        return $this->Organisations->find()->where([
            'name' => $name,
        ])->first();
    }

    private function getDataFromFile($path)
    {
        $file = new File($path);
        if ($file->exists()) {
            $this->io->verbose('Reading file');
            $text = $file->read();
            $file->close();
            if (!empty($text)) {
                $rows = array_map('str_getcsv', explode(PHP_EOL, $text));
                if (count($rows[0]) != count($rows[1])) {
                    $this->io->error('Error while parsing source data. CSV doesn\'t have the same number of columns');
                    die(1);
                }
                $csvData = [];
                $headers = array_shift($rows);
                foreach ($rows as $row) {
                    if (count($headers) == count($row)) {
                        $csvData[] = array_combine($headers, $row);
                    }
                }
                return $csvData;
            }
        }
        return false;
    }

    private function transformEntitiesIntoTable($entities, $header = [])
    {
        $table = [[]];
        if (!empty($entities)) {
            $tableHeader = empty($header) ? array_keys(Hash::flatten($entities[0]->toArray())) : $header;
            $tableContent = [];
            foreach ($entities as $entity) {
                $row = [];
                foreach ($tableHeader as $key) {
                    if (in_array($key, $entity->getVirtual())) {
                        continue;
                    }
                    $row[] = (string) $entity[$key];
                }
                $tableContent[] = $row;
            }
            $table = array_merge([$tableHeader], $tableContent);
        }
        return $table;
    }
}