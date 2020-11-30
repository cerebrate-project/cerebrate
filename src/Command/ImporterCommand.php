<?php

/**
 * Generic importer to feed data to cerebrate from JSON or CSV.
 * 
 * - JSON configuration file must have the `format` key which can either take the value `json` or `csv`
 *  - If `csv` is provided, the file must contains the header.
 *  - If `json` is provided, a `mapping` key on how to reach each fields using the cakephp4's Hash syntax must be provided.
 *      - The mapping is done in the following way:
 *          - The key is the field name
 *          - The value
 *              - Can either be the string representing the path from which to get the value
 *              - Or a JSON containg the `path`, the optional `override` parameter specifying if the existing data should be overriden 
 *                and an optional `massage` function able to alter the data.
 *          - Example
 *              {
 *                  "name": "data.{n}.team-name",
 *                  "uuid": {
 *                          "path": "data.{n}.team-name",   // a path MUST always be provided
 *                          "override": false,              // If the value already exists in the database, do not override it
 *                          "massage": "genUUID"            // The function genUUID will be called on every piece of data
 *              },
 *
 * - The optional primary key argument provides a way to make import replayable. It can typically be used when an ID or UUID is not provided in the source file but can be replaced by something else (e.g. team-name or other type of unique data).
 * 
 */

namespace App\Command;

use Cake\Console\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Filesystem\File;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Http\Client;

class ImporterCommand extends Command
{
    protected $modelClass = 'Organisations';
    private $fieldsNoOverride = [];
    private $format = 'json';

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Import data based on the provided configuration file.');
        $parser->addArgument('config', [
            'help' => 'JSON configuration file path for the importer.',
            'required' => true
        ]);
        $parser->addArgument('source', [
            'help' => 'The source that should be imported. Can be either a file on the disk or an valid URL.',
            'required' => true
        ]);
        $parser->addOption('primary_key', [
            'short' => 'p',
            'help' => 'To avoid duplicates, entries having the value specified by the primary key will be updated instead of inserted. Leave empty if only insertion should be done',
            'default' => null,
        ]);
        $parser->addOption('model_class', [
            'short' => 'm',
            'help' => 'The target cerebrate model for the import',
            'default' => 'Organisations',
            'choices' => ['Organisations', 'Individuals', 'AuthKeys']
        ]);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->io = $io;
        $configPath = $args->getArgument('config');
        $source = $args->getArgument('source');
        $primary_key = $args->getOption('primary_key');
        $model_class = $args->getOption('model_class');
        if (!is_null($model_class)) {
            $this->modelClass = $model_class;
        }

        $table = $this->modelClass;
        $this->loadModel($table);
        $config = $this->getConfigFromFile($configPath);
        $this->processConfig($config);
        $sourceData = $this->getDataFromSource($source);
        $data = $this->extractData($this->{$table}, $config, $sourceData);
        $entities = $this->marshalData($this->{$table}, $data, $config, $primary_key);

        $entitiesSample = array_slice($entities, 0, min(10, count($entities)));
        $ioTable = $this->transformEntitiesIntoTable($entitiesSample);
        $io->helper('Table')->output($ioTable);

        $selection = $io->askChoice('A sample of the data you are about to save is provided above. Would you like to proceed?', ['Y', 'N'], 'N');
        if ($selection == 'Y') {
            $this->saveData($this->{$table}, $entities);
        }
    }

    private function marshalData($table, $data, $config, $primary_key=null)
    {
        $this->loadModel('MetaFields');
        $entities = [];
        if (is_null($primary_key)) {
            $entities = $table->newEntities($data);
        } else {
            foreach ($data as $i => $item) {
                $entity = null;
                if (isset($item[$primary_key])) {
                    $query = $table->find('all')
                        ->where(["${primary_key}" => $item[$primary_key]]);
                    $entity = $query->first();
                }
                if (is_null($entity)) {
                    $entity = $table->newEmptyEntity();
                } else {
                    $this->lockAccess($entity);
                }
                $entity = $table->patchEntity($entity, $item);
                $entities[] = $entity;
            }
        }
        $hasErrors = false;
        foreach ($entities as $i => $entity) {
            if ($entity->hasErrors()) {
                $hasErrors = true;
                $this->io->error(json_encode(['entity' => $entity, 'errors' => $entity->getErrors()], JSON_PRETTY_PRINT));
            } else {
                $metaFields = [];
                foreach ($entity['metaFields'] as $fieldName => $fieldValue) {
                    $metaEntity = null;
                    if (!$entity->isNew()) {
                        $query = $this->MetaFields->find('all')->where([
                            'parent_id' => $entity->id,
                            'field' => $fieldName
                        ]);
                        $metaEntity = $query->first();
                    }
                    if (is_null($metaEntity)) {
                        $metaEntity = $this->MetaFields->newEmptyEntity();
                        $metaEntity->field = $fieldName;
                        $metaEntity->scope = $table->metaFields;
                        $metaEntity->parent_id = $entity->id;
                    }
                    if ($this->canBeOverriden($metaEntity)) {
                        $metaEntity->value = $fieldValue;
                    }
                    $metaFields[] = $metaEntity;
                }
                $entities[$i]->metaFields = $metaFields;
            }
        }
        if (!$hasErrors) {
            $this->io->verbose('No validation errors');
        } else {
            $this->io->error('Validation errors, please fix before importing');
            die(1);
        }
        return $entities;
    }

    private function saveData($table, $entities)
    {
        $this->loadModel('MetaFields');
        $this->io->verbose('Saving data');
        $progress = $this->io->helper('Progress');
        
        $entities = $table->saveMany($entities);
        if ($entities === false) {
            $this->io->error('Error while saving data');
        }
        $this->io->verbose('Saving meta fields');
        $this->io->out('');
        $progress->init([
            'total' => count($entities),
            'length' => 20
        ]);
        foreach ($entities as $i => $entity) {
            $this->saveMetaFields($entity);
            $progress->increment(1);
            $progress->draw();
        }
        $this->io->out('');
    }
    
    private function saveMetaFields($entity)
    {
        foreach ($entity->metaFields as $i => $metaEntity) {
            $metaEntity->parent_id = $entity->id;
            if ($metaEntity->hasErrors() || is_null($metaEntity->value)) {
                unset($entity->metaFields[$i]);
            }
        }
        $entity->metaFields = $this->MetaFields->saveMany($entity->metaFields);
        if ($entity->metaFields === false) {
            $this->io->error('Error while saving meta data');
        }
    }
    
    private function extractData($table, $config, $source)
    {
        $this->io->verbose('Extracting data');
        $defaultFields = array_flip($table->getSchema()->columns());
        if ($this->format == 'json') {
            $data = $this->extractDataFromJSON($defaultFields, $config, $source);
        } else if ($this->format == 'csv') {
            $data = $this->extractDataFromCSV($defaultFields, $config, $source);
        } else {
            $this->io->error('Cannot extract data: Invalid file format');
            die(1);
        }
        return $data;
    }

    private function extractDataFromJSON($defaultFields, $config, $source)
    {
        $data = [];
        foreach ($config['mapping'] as $key => $fieldConfig) {
            $values = null;
            if (!is_array($fieldConfig)) {
                $fieldConfig = ['path' =>  $fieldConfig];
            }
            if (!empty($fieldConfig['path'])) {
                $values = Hash::extract($source, $fieldConfig['path']);
            }
            if (!empty($fieldConfig['massage'])) {
                $values = array_map("self::{$fieldConfig['massage']}", $values);
            }
            if (isset($defaultFields[$key])) {
                $data[$key] = $values;
            } else {
                $data['metaFields'][$key] = $values;
            }
        }
        return $this->invertArray($data);
    }

    private function extractDataFromCSV($defaultFields, $config, $source)
    {
        $rows = array_map('str_getcsv', explode(PHP_EOL, $source));
        if (count($rows[0]) != count($rows[1])) {
            $this->io->error('Error while parsing source data. CSV doesn\'t have the same number of columns');
            die(1);
        }
        $header = array_shift($rows);
        $data = array();
        foreach($rows as $row) {
            $dataRow = [];
            foreach ($header as $i => $headerField) {
                if (isset($defaultFields[$headerField])) {
                    $dataRow[$headerField] = $row[$i];
                } else {
                    $dataRow['metaFields'][$headerField] = $row[$i];
                }
            }
            $data[] = $dataRow;
        }
        return $data;
    }

    private function lockAccess(&$entity)
    {
        foreach ($this->fieldsNoOverride as $fieldName) {
            $entity->setAccess($fieldName, false);
        }
    }

    private function canBeOverriden($metaEntity)
    {
        return !in_array($metaEntity->field, $this->fieldsNoOverride);
    }

    private function getDataFromSource($source)
    {
        $data = $this->getDataFromFile($source);
        if ($data === false) {
            $data = $this->getDataFromURL($source);
        }
        return $data;
    }

    private function getDataFromURL($url)
    {
        $validator = new Validator();
        $validator
            ->requirePresence('url')
            ->notEmptyString('url', 'Please provide a valid source')
            ->url('url');
        $errors = $validator->validate(['url' => $url]);
        if (!empty($errors)) {
            $this->io->error(json_encode(Hash::extract($errors, '{s}'), JSON_PRETTY_PRINT));
            die(1);
        }
        $http = new Client();
        $this->io->verbose('Downloading file');
        $response = $http->get($url);
        if ($this->format == 'json') {
            return $response->getJson();
        } else if ($this->format == 'csv') {
            return $response->getStringBody();
        } else {
            $this->io->error('Cannot parse source data: Invalid file format');
        }
    }

    private function getDataFromFile($path)
    {
        $file = new File($path);
        if ($file->exists()) {
            $this->io->verbose('Reading file');
            $data = $file->read();
            $file->close();
            if (!empty($data)) {
                if ($this->format == 'json') {
                    $data = json_decode($data, true);
                    if (is_null($data)) {
                        $this->io->error('Error while parsing the source file');
                        die(1);
                    }
                    return $data;
                } else if ($this->format == 'csv') {
                    return $data;
                } else {
                    $this->io->error('Cannot parse source data: Invalid file format');
                }
            }
        }
        return false;
    }

    private function getConfigFromFile($configPath)
    {
        $file = new File($configPath);
        if ($file->exists()) {
            $config = $file->read();
            $file->close();
            if (!empty($config)) {
                $config = json_decode($config, true);
                if (is_null($config)) {
                    $this->io->error('Error while parsing the configuration file');
                    die(1);
                }
                return $config;
            } else {
                $this->io->error('Configuration file cound not be read');
            }
        } else {
            $this->io->error('Configuration file not found');
        }
    }

    private function processConfig($config)
    {
        if (empty($config['mapping'])) {
            $this->io->error('Error while parsing the configuration file, mapping missing');
            die(1);
        }
        if (!empty($config['format'])) {
            $this->format = $config['format'];
        }
        $this->fieldsNoOverride = [];
        foreach ($config['mapping'] as $fieldName => $fieldConfig) {
            if (is_array($fieldConfig)) {
                if (isset($fieldConfig['override']) && $fieldConfig['override'] === false) {
                    $this->fieldsNoOverride[] = $fieldName;
                }
            }
        }
    }

    private function transformResultSetsIntoTable($result, $header=[])
    {
        $table = [[]];
        if (!empty($result)) {
            $tableHeader = empty($header) ? array_keys($result[0]) : $header;
            $tableContent = [];
            foreach ($result as $item) {
                if (empty($header)) {
                    $tableContent[] = array_map('strval', array_values($item));
                } else {
                    $row = [];
                    foreach ($tableHeader as $key) {
                        $row[] = (string) $item[$key];
                    }
                    $tableContent[] = $row;
                }
            }
            $table = array_merge([$tableHeader], $tableContent);
        }
        return $table;
    }

    private function transformEntitiesIntoTable($entities, $header=[])
    {
        $table = [[]];
        if (!empty($entities)) {
            $tableHeader = empty($header) ? array_keys(Hash::flatten($entities[0]->toArray())) : $header;
            $tableHeader = array_filter($tableHeader, function($name) {
                return !in_array('metaFields', explode('.', $name));
            });
            if (!empty($entities[0]->metaFields)) {
                foreach ($entities[0]->metaFields as $metaField) {
                    $tableHeader[] = "metaFields.$metaField->field";
                }
            }
            $tableContent = [];
            foreach ($entities as $entity) {
                $row = [];
                foreach ($tableHeader as $key) {
                    $subKeys = explode('.', $key);
                    if (in_array('metaFields', $subKeys)) {
                        $found = false;
                        foreach ($entity->metaFields as $metaField) {
                            if ($metaField->field == $subKeys[1]) {
                                $row[] = (string) $metaField->value;
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $row[] = '';
                        }
                    } else {
                        $row[] = (string) $entity[$key];
                    }
                }
                $tableContent[] = $row;
            }
            $table = array_merge([$tableHeader], $tableContent);
        }
        return $table;
    }

    private function invertArray($data)
    {
        $inverted = [];
        foreach ($data as $key => $values) {
            if ($key == 'metaFields') {
                foreach ($values as $metaKey => $metaValues) {
                    foreach ($metaValues as $i => $metaValue) {
                        $inverted[$i]['metaFields'][$metaKey] = $metaValue;
                    }
                }
            } else {
                foreach ($values as $i => $value) {
                    $inverted[$i][$key] = $value;
                }
            }
        }
        return $inverted;
    }

    private function genUUID($value)
    {
        return Text::uuid();
    }
}