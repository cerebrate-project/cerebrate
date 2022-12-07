<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class Individual extends AppModel
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'uuid' => false,
        'created' => false,
    ];

    protected $_accessibleOnNew = [
        'uuid' => true,
        'created' => true,
    ];

    protected $_virtual = ['full_name', 'alternate_emails'];

    protected function _getFullName()
    {
        if (empty($this->first_name) && empty($this->last_name)) {
            return $this->username;
        }
        return sprintf("%s %s", $this->first_name, $this->last_name);
    }

    protected function _getAlternateEmails()
    {
        $emails = [];
        if (!empty($this->meta_fields)) {
            foreach ($this->meta_fields as $metaField) {
                if (!empty($metaField->field) && str_contains($metaField->field, 'email')) {
                    $emails[] = $metaField;
                }
            }
        }
        return $emails;
    }

    public function rearrangeForAPI(array $options = []): void
    {
        if (!empty($this->tags)) {
            $this->tags = $this->rearrangeTags($this->tags);
        }
        if (!empty($this->alignments)) {
            $this->alignments = $this->rearrangeAlignments($this->alignments);
        }
        if (!empty($this->meta_fields)) {
            $this->rearrangeMetaFields($options);
        }
    }
}
