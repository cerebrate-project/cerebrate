<?php

use Cake\Utility\Inflector;

$default_template = [
    'inputContainer' => '<div class="row mb-3 metafield-container">{{content}}</div>',
    'inputContainerError' => '<div class="row mb-3 metafield-container has-error">{{content}}</div>',
    'formGroup' => '<div class="col-sm-2 form-label" {{attrs}}>{{label}}</div><div class="col-sm-10">{{input}}{{error}}</div>',
];
$this->Form->setTemplates($default_template);
$backupTemplates = $this->Form->getTemplates();
$tabData = [];
foreach ($metaTemplatesData as $i => $metaTemplate) {
    if ($metaTemplate->is_default) {
        $tabData['navs'][$i] = [
            'html' => $this->element('/genericElements/MetaTemplates/metaTemplateNav', ['metaTemplate' => $metaTemplate])
        ];
    } else {
        $tabData['navs'][$i] = [
            'text' => $metaTemplate->name
        ];
    }
    $fieldsHtml = '';
    foreach ($metaTemplate->meta_template_fields as $metaTemplateField) {
        $metaTemplateField->label = Inflector::humanize($metaTemplateField->field);
        if (!empty($metaTemplateField->metaFields)) {
            if (!empty($metaTemplateField->multiple)) {
                $fieldsHtml .= $this->element(
                    'genericElements/Form/multiFieldScaffold',
                    [
                        'metaFieldsEntities' => $metaTemplateField->metaFields,
                        'metaTemplateField' => $metaTemplateField,
                        'multiple' => !empty($metaTemplateField->multiple),
                        'form' => $this->Form,
                    ]
                );
            } else {
                $metaField = reset($metaTemplateField->metaFields);
                $fieldData = [
                    'field' => sprintf('MetaTemplates.%s.meta_template_fields.%s.metaFields.%s.value', $metaField->meta_template_id, $metaField->meta_template_field_id, $metaField->id),
                    'label' => $metaTemplateField->field,
                ];
                $fieldsHtml .= $this->element(
                    'genericElements/Form/fieldScaffold',
                    [
                        'fieldData' => $fieldData,
                        'metaTemplateField' => $metaTemplateField,
                        'form' => $this->Form
                    ]
                );
            }
        } else {
            $this->Form->setTemplates($backupTemplates);
            $fieldData = [
                'field' => sprintf('MetaTemplates.%s.meta_template_fields.%s.metaFields.new.0', $metaTemplateField->meta_template_id, $metaTemplateField->id),
                'label' => $metaTemplateField->field,
            ];
            $fieldsHtml .= $this->element(
                'genericElements/Form/fieldScaffold',
                [
                    'fieldData' => $fieldData,
                    'form' => $this->Form
                ]
            );
        }
    }
    $tabData['content'][$i] = $fieldsHtml;
}
echo $this->Bootstrap->Tabs([
    'pills' => true,
    'data' => $tabData,
    'nav-class' => ['mb-3']
]);
