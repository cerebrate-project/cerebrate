<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;

class MailingList extends AppModel
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'uuid' => false,
        'user_id' => false,
    ];

    protected $_accessibleOnNew = [
        'uuid' => true,
        'user_id' => true,
    ];

    private $metaFieldsByParentId = [];

    public function injectRegisteredEmailsIntoIndividuals()
    {
        if (empty($this->individuals)) {
            return;
        }
        if (!empty($this->meta_fields)) {
            foreach ($this->meta_fields as $meta_field) {
                $this->metaFieldsByParentId[$meta_field->parent_id][] = $meta_field;
            }
        }
        foreach ($this->individuals as $i => $individual) {
            $this->individuals[$i]->mailinglist_emails = $this->collectEmailsForMailingList($individual);
        }
    }

    protected function collectEmailsForMailingList($individual)
    {
        $emails = [];
        if (!empty($individual['_joinData']) && !empty($individual['_joinData']['include_primary_email'])) {
            $emails[] = $individual->email;
        }
        if (!empty($this->metaFieldsByParentId[$individual->id])) {
            foreach ($this->metaFieldsByParentId[$individual->id] as $metaField) {
                $emails[] = $metaField->value;
            }
        }
        return $emails;
    }
}
