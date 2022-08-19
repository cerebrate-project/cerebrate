<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use App\Model\Entity\User;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Utility\Inflector;
use Cake\Routing\Router;

class APIRearrangeComponent extends Component
{
    
    private $rearrangeFunctions = [
        'App\Model\Entity\Organisation' => 'rearrangeOrganisation'
    ];

    public function rearrange(object $data): object
    {
        if (get_class($data) === 'Cake\ORM\ResultSet') {
            $data->each(function ($value, $key) {
                $value = $this->rearrangeEntity($value);
            });
        } else {
            $data = $this->rearrangeEntity($data);
        }
        return $data;
    }

    private function rearrangeEntity(object $entity): object
    {
        $entityClass = get_class($entity);
        if (isset($this->rearrangeFunctions[$entityClass])) {
            $entity = $this->{$this->rearrangeFunctions[$entityClass]}($entity);
        }
        return $entity;
    }

    private function rearrangeOrganisation(object $entity): object
    {   
        if (!empty($entity['tags'])) {
            $entity['tags'] = $this->rearrangeTags($entity['tags']);
        }
        if (!empty($entity['alignments'])) {
            $entity['alignments'] = $this->rearrangeAlignments($entity['alignments']);
        }
        if (!empty($entity['meta_fields'])) {
            $entity = $this->rearrangeMetaFields($entity);
        }
        if (!empty($entity['MetaTemplates'])) {
            unset($entity['MetaTemplates']);
        }
        return $entity;
    }

    private function rearrangeMetaFields(object $entity): object
    {
        $entity['meta_fields'] = [];
        foreach ($entity['MetaTemplates'] as $template) {
            foreach ($template['meta_template_fields'] as $field) {
                if ($field['counter'] > 0) {
                    foreach ($field['metaFields'] as $metaField) {
                        if (!empty($entity['meta_fields'][$template['name']][$field['field']])) {
                            if (!is_array($entity['meta_fields'][$template['name']])) {
                                $entity['meta_fields'][$template['name']][$field['field']] = [$entity['meta_fields'][$template['name']][$field['field']]];
                            }
                            $entity['meta_fields'][$template['name']][$field['field']][] = $metaField['value'];
                        } else {
                            $entity['meta_fields'][$template['name']][$field['field']] = $metaField['value'];
                        }
                    }
                }
            }
        }
        return $entity;
    }

    private function rearrangeTags(array $tags): array
    {
        foreach ($tags as &$tag) {
            unset($tag['_joinData']);
        }
        return $tags;
    }

    private function rearrangeAlignments(array $alignments): array
    {
        $rearrangedAlignments = [];
        $validAlignmentTypes = ['individual', 'organisation'];
        foreach ($alignments as $alignment) {
            //debug($alignment);
            foreach ($validAlignmentTypes as $type) {
                if (isset($alignment[$type])) {
                    $alignment[$type]['type'] = $alignment['type'];
                    $rearrangedAlignments[$type][] = $alignment[$type];
                }
            }
        }
        return $rearrangedAlignments;
    }
}