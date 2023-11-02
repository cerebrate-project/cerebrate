<?php

namespace App\Model\Entity;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class AppModel extends Entity
{
    const BROTLI_HEADER = "\xce\xb2\xcf\x81";
    const BROTLI_MIN_LENGTH = 200;

    const ACTION_ADD = 'add',
        ACTION_EDIT = 'edit',
        ACTION_SOFT_DELETE = 'soft_delete',
        ACTION_DELETE = 'delete',
        ACTION_UNDELETE = 'undelete',
        ACTION_TAG = 'tag',
        ACTION_TAG_LOCAL = 'tag_local',
        ACTION_REMOVE_TAG = 'remove_tag',
        ACTION_REMOVE_TAG_LOCAL = 'remove_local_tag',
        ACTION_LOGIN = 'login',
        ACTION_LOGIN_FAIL = 'login_fail',
        ACTION_LOGOUT = 'logout';


    public function getConstant($name)
    {
        return constant('self::' . $name);
    }

    public function getAccessibleFieldForNew(): array
    {
        return $this->_accessibleOnNew ?? [];
    }

    public function table(): AppTable
    {
        return TableRegistry::get($this->getSource());
    }

    public function rearrangeForAPI(array $options = []): void
    {
    }

    public function rearrangeMetaFields(array $options = []): void
    {
        if (!empty($options['includeFullMetaFields'])) {
            $this->meta_fields = [];
            foreach ($this->MetaTemplates as $template) {
                foreach ($template['meta_template_fields'] as $field) {
                    if ($field['counter'] > 0) {
                        foreach ($field['metaFields'] as $metaField) {
                            if (!empty($this->meta_fields[$template['name']][$field['field']])) {
                                if (!is_array($this->meta_fields[$template['name']][$field['field']])) {
                                    $this->meta_fields[$template['name']][$field['field']] = [$this->meta_fields[$template['name']][$field['field']]];
                                }
                                $this->meta_fields[$template['name']][$field['field']][] = $metaField['value'];
                            } else {
                                $this->meta_fields[$template['name']][$field['field']] = $metaField['value'];
                            }
                        }
                    }
                }
            }
        } elseif (!empty($this->meta_fields)) {
            $templateDirectoryTable = TableRegistry::get('MetaTemplateNameDirectory');
            $templates = [];
            foreach ($this->meta_fields as $i => $metafield) {
                $templateDirectoryId = $metafield['meta_template_directory_id'];
                if (empty($templates[$templateDirectoryId])) {
                    $templates[$templateDirectoryId] = $templateDirectoryTable->find()->where(['id' => $templateDirectoryId])->first();
                }
                $this->meta_fields[$i]['template_uuid'] = $templates[$templateDirectoryId]['uuid'];
                $this->meta_fields[$i]['template_version'] = $templates[$templateDirectoryId]['version'];
                $this->meta_fields[$i]['template_name'] = $templates[$templateDirectoryId]['name'];
                $this->meta_fields[$i]['template_namespace'] = $templates[$templateDirectoryId]['namespace'];
            }
        }
        if (!empty($options['smartFlattenMetafields'])) {
            $smartFlatten = [];
            foreach ($this->meta_fields as $metafield) {
                $key = "{$metafield['template_name']}_v{$metafield['template_version']}:{$metafield['field']}";
                $value = $metafield['value'];
                $smartFlatten[$key] = $value;
            }
            $this->meta_fields = $smartFlatten;
        }
        // if ((!isset($options['includeMetatemplate']) || empty($options['includeMetatemplate'])) && !empty($this->MetaTemplates)) {
        if ((!isset($options['includeMetatemplate']) || empty($options['includeMetatemplate']))) {
            unset($this->MetaTemplates);
        }
    }

    public function rearrangeTags(array $tags): array
    {
        foreach ($tags as &$tag) {
            $tag = [
                'id' => $tag['id'],
                'name' => $tag['name'],
                'colour' => $tag['colour']
            ];
        }
        return $tags;
    }

    public function rearrangeAlignments(array $alignments): array
    {
        $rearrangedAlignments = [];
        $validAlignmentTypes = ['individual', 'organisation'];
        $alignmentDataToKeep = [
            'individual' => [
                'id',
                'email'
            ],
            'organisation' => [
                'id',
                'uuid',
                'name'
            ]
        ];
        foreach ($alignments as $alignment) {
            foreach (array_keys($alignmentDataToKeep) as $type) {
                if (isset($alignment[$type])) {
                    $alignment[$type]['type'] = $alignment['type'];
                    $temp = [];
                    foreach ($alignmentDataToKeep[$type] as $field) {
                        $temp[$field] = $alignment[$type][$field];
                    }
                    $rearrangedAlignments[$type][] = $temp;
                }
            }
        }
        return $rearrangedAlignments;
    }

    public function rearrangeSimplify(array $typesToRearrange): void
    {
        if (in_array('organisation', $typesToRearrange) && isset($this->organisation)) {
            $this->organisation = [
                'id' => $this->organisation['id'],
                'name' => $this->organisation['name'],
                'uuid' => $this->organisation['uuid']
            ];
        }
        if (in_array('individual', $typesToRearrange) && isset($this->individual)) {
            $this->individual = [
                'id' => $this->individual['id'],
                'email' => $this->individual['email'],
                'uuid' => $this->individual['uuid']
            ];
        }
    }
}
