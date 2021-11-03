<?php
$default_template = [
    'inputContainer' => '<div class="row pb-1 multi-metafield-container">{{content}}</div>',
    'inputContainerError' => '<div class="row pb-1 metafield-container has-error">{{content}}</div>',
    'formGroup' => '<div class="col-sm-2 form-label" {{attrs}}>{{label}}</div><div class="col-sm-10">{{input}}{{error}}</div>',
];
$form->setTemplates($default_template);

$fieldsHtml = '';
$labelPrintedOnce = false;
foreach ($metaFieldsEntities as $i => $metaFieldsEntity) {
    $fieldData = [
        'label' => $metaFieldsEntity->field,
        'field' => sprintf('MetaTemplates.%s.meta_template_fields.%s.metaFields.%s.value', $metaFieldsEntity->meta_template_id, $metaFieldsEntity->meta_template_field_id, $metaFieldsEntity->id),
    ];
    if ($labelPrintedOnce) { // Only the first input can have a label
        $fieldData['label'] = false;
    }
    $labelPrintedOnce = true;
    $fieldsHtml .= $this->element(
        'genericElements/Form/fieldScaffold',
        [
            'fieldData' => $fieldData,
            'form' => $form
        ]
    );
}
if (!empty($metaTemplateField) && !empty($multiple)) { // Add multiple field button
    $emptyInputForSecurityComponent = $this->element(
        'genericElements/Form/fieldScaffold',
        [
            'fieldData' => [
                'label' => false,
                'field' => sprintf('MetaTemplates.%s.meta_template_fields.%s.metaFields.new[]', $metaFieldsEntity->meta_template_id, $metaFieldsEntity->meta_template_field_id),
            ],
            'form' => $form,
        ]
    );
    $multiFieldButtonHtml = sprintf(
        '<div class="row pb-1 multi-metafield-container"><div class="col-sm-2 form-label"></div><div class="col-sm-10">%s</div></div>',
        $this->element(
            'genericElements/Form/multiFieldButton',
            [
                'metaTemplateFieldName' => $metaTemplateField->field,
            ]
        )
    );
    $fieldsHtml .= sprintf('<div class="d-none template-container">%s</div>', $emptyInputForSecurityComponent);
    $fieldsHtml .= $multiFieldButtonHtml;
}
?>

<div class="row mb-3 multi-metafields-container">
    <?= $fieldsHtml; ?>
</div>