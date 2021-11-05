    <?php
$tabData = [
    'navs' => [],
    'content' => []
];
foreach($data['MetaTemplates'] as $metaTemplate) {
    if (!empty($metaTemplate->meta_template_fields)) {
        if ($metaTemplate->is_default) {
            $tabData['navs'][] = [
                'html' => $this->element('/genericElements/MetaTemplates/metaTemplateNav', ['metaTemplate' => $metaTemplate])
            ];
        } else {
            $tabData['navs'][] = [
                'text' => $metaTemplate->name
            ];
        }
        $fields = [];
        foreach ($metaTemplate->meta_template_fields as $metaTemplateField) {
            $labelPrintedOnce = false;
            foreach ($metaTemplateField->metaFields as $metaField) {
                $fields[] = [
                    'key' => !$labelPrintedOnce ? $metaField->field : '',
                    'raw' => $metaField->value
                ];
                $labelPrintedOnce = true;
            }
        }
        $listTable = $this->Bootstrap->listTable([
            'hover' => false,
            'elementsRootPath' => '/genericElements/SingleViews/Fields/'
        ],[
            'item' => false,
            'fields' => $fields
        ]);
        $tabData['content'][] = $listTable;
    }
}
if (!empty($additionalTabs)) {
    $tabData['navs'] = array_merge($additionalTabs['navs'], $tabData['navs']);
    $tabData['content'] = array_merge($additionalTabs['content'], $tabData['content']);
}
if (!empty($tabData['navs'])) {
    $metaTemplateTabs = $this->Bootstrap->Tabs([
        'pills' => true,
        'card' => true,
        'body-class' => ['p-1'],
        'data' => $tabData
    ]);
}
echo $metaTemplateTabs;