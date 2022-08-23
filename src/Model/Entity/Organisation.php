<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class Organisation extends AppModel
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'created' => false
    ];

    protected $_accessibleOnNew = [
        'created' => true
    ];

    public function rearrangeForAPI(): void
    {
        if (!empty($this->tags)) {
            $this->tags = $this->rearrangeTags($this->tags);
        }
        if (!empty($this->alignments)) {
            $this->alignments = $this->rearrangeAlignments($this->alignments);
        }
        if (!empty($this->meta_fields)) {
            $this->rearrangeMetaFields();
        }
        if (!empty($this->MetaTemplates)) {
            unset($this->MetaTemplates);
        }
    }
}
