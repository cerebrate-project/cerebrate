<?php
use Cake\Utility\Inflector;

$tabData = [];
foreach($metaTemplatesData as $i => $metaTemplate) {
    $tabData['navs'][$i] = [
        'text' => $metaTemplate->name
    ];
    $fieldsHtml = '';
    foreach ($metaTemplate->meta_template_fields as $metaField) {
        $metaField->label = Inflector::humanize($metaField->field);
        $metaField->field = sprintf('%s.%s.%s', 'metaFields', $metaField->meta_template_id, $metaField->field);
        $fieldsHtml .= $this->element(
            'genericElements/Form/fieldScaffold', [
                'fieldData' => $metaField->toArray(),
                'form' => $this->Form
            ]
        );
    }
    $tabData['content'][$i] = $fieldsHtml;
}
echo $this->Bootstrap->Tabs([
    'pills' => true,
    'data' => $tabData,
    'nav-class' => ['pb-1']
]);