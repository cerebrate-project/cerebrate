<?php
    /*
     * Generic form builder
     *
     * Simply pass a JSON with the following keys set:
     * - model: The model used to create the form (such as Attribute, Event)
     * - fields: an array with each element generating an input field
     *     - field is the actual field name (such as org_id, name, etc) which is required
     *     - optional fields: default, type, options, placeholder, label - these are passed directly to $this->Form->input(),
     *     - requirements: boolean, if false is passed the field is skipped
     * - metafields: fields that are outside of the scope of the form itself
           - use these to define dynamic form fields, or anything that will feed into the regular fields via JS population
     * - submit: The submit button itself. By default it will simply submit to the form as defined via the 'model' field
     */
    $this->Form->setConfig('errorClass', 'is-invalid');
    $modelForForm = empty($data['model']) ?
        h(\Cake\Utility\Inflector::singularize(\Cake\Utility\Inflector::classify($this->request->getParam('controller')))) :
        h($data['model']);
    $entity = isset($entity) ? $entity : null;
    $fieldsString = '';
    $simpleFieldWhitelist = [
        'default', 'type', 'placeholder', 'label', 'empty', 'rows', 'div', 'required'
    ];
    //$fieldsArrayForPersistence = array();
    if (empty($data['url'])) {
        $data['url'] = ["controller" => $this->request->getParam('controller'), "action" => $this->request->getParam('url')];
    }
    $formRandomValue = Cake\Utility\Security::randomString(8);
    $formCreate = $this->Form->create($entity, ['id' => 'form-' . $formRandomValue]);
    $default_template = [
        'inputContainer' => '<div class="row mb-3">{{content}}</div>',
        'inputContainerError' => '<div class="row mb-3 has-error">{{content}}</div>',
        'label' => '{{text}}',
        'input' => '<input type="{{type}}" name="{{name}}"{{attrs}} />',
        'textarea' => '<textarea name="{{name}}" {{attrs}}>{{value}}</textarea>',
        'select' => '<select name="{{name}}" {{attrs}}>{{content}}</select>',
        'checkbox' => '<input type="checkbox" name="{{name}}" value="{{value}}"{{attrs}}>',
        'checkboxFormGroup' => '{{label}}',
        'formGroup' => '<div class="col-sm-2 form-label" {{attrs}}>{{label}}</div><div class="col-sm-10">{{input}}{{error}}</div>',
        'nestingLabel' => '{{hidden}}<div class="col-sm-2 form-label">{{text}}</div><div class="col-sm-10">{{input}}</div>',
        'option' => '<option value="{{value}}"{{attrs}}>{{text}}</option>',
        'optgroup' => '<optgroup label="{{label}}"{{attrs}}>{{content}}</optgroup>',
        'select' => '<select name="{{name}}"{{attrs}}>{{content}}</select>',
        'error' => '<div class="error-message invalid-feedback d-block">{{content}}</div>',
        'errorList' => '<ul>{{content}}</ul>',
        'errorItem' => '<li>{{text}}</li>',
    ];
    if (!empty($data['fields'])) {
        foreach ($data['fields'] as $fieldData) {
            if (!empty($fields)) {
                if (!in_array($fieldData['field'], $fields)) {
                    continue;
                }
            }
            $formTemplate = $default_template;
            if (!empty($fieldData['floating-label'])) {
                $formTemplate['inputContainer'] = '<div class="form-floating input {{type}}{{required}}">{{content}}</div>';
                $formTemplate['label'] = '<label{{attrs}}>{{text}}</label>';
                $formTemplate['formGroup'] = '{{input}}{{label}}';
                $fieldData['placeholder'] = !empty($fieldData['label']) ? $fieldData['label'] : h($fieldData['field']);
            }
            // we reset the template each iteration as individual fields might override the defaults.
            $this->Form->setConfig($formTemplate);
            $this->Form->setTemplates($formTemplate);
            if (isset($fieldData['requirements']) && !$fieldData['requirements']) {
                continue;
            }
            $fieldsString .= $this->element(
                'genericElements/Form/fieldScaffold', [
                    'fieldData' => $fieldData,
                    'form' => $this->Form,
                    'simpleFieldWhitelist' => $simpleFieldWhitelist
                ]
            );
        }
    }
    if (!empty($data['metaTemplates']) && $data['metaTemplates']->count() > 0) {
        $metaTemplateString = $this->element(
            'genericElements/Form/metaTemplateScaffold', [
                'metaTemplatesData' => $data['metaTemplates'],
                'form' => $this->Form,
            ]
        );
    }
    $submitButtonData = ['model' => $modelForForm, 'formRandomValue' => $formRandomValue];
    if (!empty($data['submit'])) {
        $submitButtonData = array_merge($submitButtonData, $data['submit']);
    }
    if (!empty($data['ajaxSubmit'])) {
        $submitButtonData['ajaxSubmit'] = $ajaxSubmit;
    }
    $ajaxFlashMessage = '';
    if (!empty($ajax)) {
        $ajaxFlashMessage = sprintf(
            '<div id="flashContainer"><div id="main-view-container">%s</div></div>',
            $this->Flash->render()
        );
    }
    $formEnd = $this->Form->end();
    $actionName = h(\Cake\Utility\Inflector::humanize($this->request->getParam('action')));
    $modelName = h(\Cake\Utility\Inflector::humanize(\Cake\Utility\Inflector::singularize($this->request->getParam('controller'))));
    if (!empty($ajax)) {
        echo $this->element('genericElements/genericModal', [
            'title' => empty($data['title']) ? sprintf('%s %s', $actionName, $modelName) : h($data['title']),
            'body' => sprintf(
                '%s%s%s%s%s%s',
                empty($data['description']) ? '' : sprintf(
                    '<div class="pb-2 fw-light">%s</div>',
                    h($data['description'])
                ),
                $ajaxFlashMessage,
                $formCreate,
                $fieldsString,
                empty($metaTemplateString) ? '' : $this->element(
                    'genericElements/accordion_scaffold', [
                        'children' => [
                            [
                                'body' => $metaTemplateString,
                                'title' => 'Meta fields'
                            ]
                        ]
                    ]
                ),
                $formEnd
            ),
            'actionButton' => $this->element('genericElements/Form/submitButton', $submitButtonData),
            'class' => 'modal-lg'
        ]);
    } else if (!empty($raw)) {
        echo sprintf(
            '%s%s%s%s%s%s',
            empty($data['description']) ? '' : sprintf(
                '<div class="pb-2">%s</div>',
                h($data['description'])
            ),
            $ajaxFlashMessage,
            $formCreate,
            $fieldsString,
            empty($metaTemplateString) ? '' : $this->element(
                'genericElements/accordion_scaffold', [
                    'children' => [
                        [
                            'body' => $metaTemplateString,
                            'title' => 'Meta fields'
                        ]
                    ]
                ]
            ),
            $formEnd
        );
    } else {
        echo sprintf(
            '%s<h2 class="fw-light">%s</h2>%s%s%s%s%s%s%s%s%s',
            empty($ajax) ? '<div class="col-8">' : '',
            empty($data['title']) ? sprintf('%s %s', $actionName, $modelName) : h($data['title']),
            $formCreate,
            $ajaxFlashMessage,
            empty($data['description']) ? '' : sprintf(
                '<div class="pb-3 fw-light">%s</div>',
                h($data['description'])
            ),
            sprintf('<div class="panel">%s</div>', $fieldsString),
            empty($metaTemplateString) ? '' : $this->element(
                'genericElements/accordion_scaffold', [
                    'children' => [
                        [
                            'body' => $metaTemplateString,
                            'title' => 'Meta fields',
                        ]
                    ],
                    'class' => 'mb-2'
                ]
            ),
            $this->element('genericElements/Form/submitButton', $submitButtonData),
            $formEnd,
            '<br /><br />',
            empty($ajax) ? '</div>' : ''
        );
    }
?>
<script type="text/javascript">
    $(document).ready(function() {
        executeStateDependencyChecks();
        $('.formDropdown').on('change', function() {
            executeStateDependencyChecks('#' + this.id);
        })
    });
</script>
