<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class MetaTemplateField extends AppModel
{
    protected $_virtual = ['form_type', 'form_options', ];

    protected function _getFormType()
    {
        $formType = 'text';
        if (!empty($this->sane_default) || !empty($this->values_list)) {
            $formType = 'dropdown';
        }
        return $formType;
    }

    protected function _getFormOptions()
    {
        $formOptions = [];
        if ($this->formType === 'dropdown') {
            $selectOptions = !empty($this->sane_default) ? $this->sane_default : $this->values_list;
            $selectOptions = array_combine($selectOptions, $selectOptions);
            if (!empty($this->sane_default)) {
                // $selectOptions['_custom'] = __('-- custom value --');
                $selectOptions[] = ['value' => '_custom', 'text' => __('-- custom value --'), 'class' => 'custom-value'];
            }
            $selectOptions[''] = __('-- no value --');
            $formOptions['options'] = $selectOptions;
            // $formOptions['empty'] = [['value' => '', 'text' => __('-- select an options --'), 'hidden disabled selected value']];
        }
        return $formOptions;
    }

}
