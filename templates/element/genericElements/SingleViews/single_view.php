<?php
/*
 *  echo $this->element('/genericElements/SingleViews/single_view', [
 *      'title' => '' //page title,
 *      'description' => '' //description,
 *      'description_html' => '' //html description, unsanitised,
 *      'data' => $data, // the raw data passed for display
 *      'fields' => [
 *           elements passed as to be displayed in the <ul> element.
 *           format:
 *           [
 *               'key' => '' // key to be displayed
 *               'path' => '' // path for the value to be parsed
 *               'type' => '' // generic assumed if not filled, uses SingleViews/Fields/* elements
 *           ]
 *      ],
 *      'children' => [
 *          // Additional elements attached to the currently viewed object. index views will be appended via ajax calls below.
*          [
 *               'title' => '',
 *               'url' => '', //cakephp compatible url, can be actual url or array for the constructor
 *               'collapsed' => 0|1  // defaults to 0, whether to display it by default or not
 *               'loadOn' => 'ready|expand'  // load the data directly or only when expanded from a collapsed state
 *
 *          ],
 *      ],
 *      'skip_meta_templates' => false // should the meta templates not be displayed
 *  ]);
 *
 */
    $tableRandomValue = Cake\Utility\Security::randomString(8);
    $listElements = '';
    if (!empty($fields)) {
        foreach ($fields as $field) {
            if (empty($field['type'])) {
                $field['type'] = 'generic';
            }
            $listElements .= sprintf(
                "<tr class=\"row\">
                    <td class=\"col-sm-2 font-weight-bold\">%s</td>
                    <td class=\"col-sm-10\">%s</td>
                </tr>",
                h($field['key']),
                $this->element(
                    '/genericElements/SingleViews/Fields/' . $field['type'] . 'Field',
                    ['data' => $data, 'field' => $field]
                )
            );
        }
    }
    $metaTemplateTabs = '';
    if (!empty($data['metaTemplates']) && (empty($skip_meta_templates))) {
        $tabData = [
            'navs' => [],
            'content' => []
        ];
        foreach($data['metaTemplates'] as $metaTemplate) {
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
                $fieldsHtml = '<table class="table table-striped">';
                foreach ($metaTemplate->meta_template_fields as $metaTemplateField) {
                    $metaField = $metaTemplateField->meta_fields[0];
                    $fieldsHtml .= sprintf(
                        '<tr class="row"><td class="col-sm-2 font-weight-bold">%s</td><td class="col-sm-10">%s</td></tr>',
                        h($metaField->field),
                        $this->element(
                            '/genericElements/SingleViews/Fields/genericField',
                            [
                                'data' => $metaField->value,
                                'field' => [
                                    'raw' => $metaField->value
                                ]
                            ]
                        )
                    );
                }
                $fieldsHtml .= '</table>';
                $tabData['content'][] = $fieldsHtml;
            }
        }
        if (!empty($tabData['navs'])) {
            $metaTemplateTabs = $this->Bootstrap->Tabs([
               'pills' => true,
               'card' => true,
               'data' => $tabData
           ]);
        }
    }
    $ajaxLists = '';
    if (!empty($children)) {
        foreach ($children as $child) {
            $ajaxLists .= $this->element(
                '/genericElements/SingleViews/child',
                array(
                    'child' => $child,
                    'data' => $data
                )
            );
        }
    }
    $title = empty($title) ?
        __('{0} view', \Cake\Utility\Inflector::singularize(\Cake\Utility\Inflector::humanize($this->request->getParam('controller')))) :
        $title;
    echo sprintf(
        "<div id=\"single-view-table-container-%s\">
            <h2>%s</h2>
            %s%s
            <div class=\"px-3\">
                <table id=\"single-view-table-%s\" class=\"table table-striped col-sm-8\">%s</table>
            </div>
            <div id=\"metaTemplates\" class=\"col-lg-8 px-0\">%s</div>
            <div id=\"accordion\">%s</div>
        </div>",
        $tableRandomValue,
        h($title),
        empty($description) ? '' : sprintf('<p>%s</p>', h($description)),
        empty($description_html) ? '' : sprintf('<p>%s</p>', $description_html),
        $tableRandomValue,
        $listElements,
        $metaTemplateTabs,
        $ajaxLists
    );
?>
