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
    $modelForForm = empty($data['model']) ?
        h(\Cake\Utility\Inflector::singularize(\Cake\Utility\Inflector::classify($this->request->getParam('controller')))) :
        h($data['model']);
    $fieldsString = '';
    $simpleFieldWhitelist = array(
        'default', 'type', 'options', 'placeholder', 'label', 'empty', 'rows', 'div', 'required'
    );
    $fieldsArrayForPersistence = array();
    if (empty($data['url'])) {
        $data['url'] = ["controller" => $this->request->getParam('controller'), "action" => $this->request->getParam('url')];
    }
    $formRandomValue = Cake\Utility\Security::randomString(8);
    $formCreate = $this->Form->create($data['entity'], ['id' => 'form-' . $formRandomValue]);
    $default_template = [
        'inputContainer' => '<div class="form-group row">{{content}}</div>',
        'inputContainerError' => '<div class="form-group row has-error">{{content}}</div>',
        'label' => '{{text}}',
        'input' => '<input type="{{type}}" name="{{name}}"{{attrs}} />',
        'textarea' => '<textarea name="{{name}}" {{attrs}}>{{value}}</textarea>',
        'select' => '<select name="{{name}}" {{attrs}}>{{content}}</select>',
        'checkbox' => '<input type="checkbox" name="{{name}}" value="{{value}}"{{attrs}}>',
        'checkboxFormGroup' => '{{label}}',
        'checkboxWrapper' => '<div class="checkbox">{{label}}</div>',
        'formGroup' => '<div class="col-sm-2 col-form-label" {{attrs}}>{{label}}</div><div class="col-sm-10">{{input}}</div>',
        'nestingLabel' => '{{hidden}}<div class="col-sm-2 col-form-label">{{text}}</div><div class="col-sm-10">{{input}}</div>',
    ];
    if (!empty($data['fields'])) {
        foreach ($data['fields'] as $fieldData) {
            // we reset the template each iteration as individual fields might override the defaults.
            $this->Form->setTemplates($default_template);
            if (isset($fieldData['requirements']) && !$fieldData['requirements']) {
                continue;
            }
            if (is_array($fieldData)) {
                if (empty($fieldData['type'])) {
                    $fieldData['type'] = 'text';
                }
                $fieldTemplate = 'genericField';
                if (file_exists(ROOT . '/templates/element/genericElements/Form/Fields/' . $fieldData['type'] . 'Field.php')) {
                    $fieldTemplate = $fieldData['type'] . 'Field';
                }
                if (empty($fieldData['label'])) {
                    $fieldData['label'] = \Cake\Utility\Inflector::humanize($fieldData['field']);
                }
                if (!empty($fieldDesc[$fieldData['field']])) {
                    $fieldData['label'] .= $this->element(
                        'genericElements/Form/formInfo', array(
                            'field' => $fieldData,
                            'fieldDesc' => $fieldDesc[$fieldData['field']],
                            'modelForForm' => $modelForForm
                        )
                    );
                }
                $params = array();
                if (!empty($fieldData['class'])) {
                    if (is_array($fieldData['class'])) {
                        $class = implode(' ', $fieldData['class']);
                    } else {
                        $class = $fieldData['class'];
                    }
                    $params['class'] = $class;
                } else {
                    $params['class'] = '';
                }
                if (empty($fieldData['type']) || $fieldData['type'] !== 'checkbox' ) {
                    $params['class'] .= ' form-control';
                }
                //$params['class'] = sprintf('form-control %s', $params['class']);
                foreach ($simpleFieldWhitelist as $f) {
                    if (!empty($fieldData[$f])) {
                        $params[$f] = $fieldData[$f];
                    }
                }
                $temp = $this->element('genericElements/Form/Fields/' . $fieldTemplate, array(
                    'fieldData' => $fieldData,
                    'params' => $params,
                    'form' => $this->Form
                ));
                if (!empty($fieldData['hidden'])) {
                    $temp = '<span class="hidden">' . $temp . '</span>';
                }
                $fieldsString .= $temp;
                $fieldsArrayForPersistence []= $modelForForm . \Cake\Utility\Inflector::camelize($fieldData['field']);
            } else {
                $fieldsString .= $fieldData;
            }
        }
    }
    $metaFieldString = '';
    if (!empty($data['metaFields'])) {
        foreach ($data['metaFields'] as $metaField) {
            $metaFieldString .= $metaField;
        }
    }
    $submitButtonData = array('model' => $modelForForm, 'formRandomValue' => $formRandomValue);
    if (!empty($data['submit'])) {
        $submitButtonData = array_merge($submitButtonData, $data['submit']);
    }
    if (!empty($data['ajaxSubmit'])) {
        $submitButtonData['ajaxSubmit'] = $ajaxSubmit;
    }
    $ajaxFlashMessage = '';
    if ($ajax) {
        $ajaxFlashMessage = sprintf(
            '<div id="flashContainer"><div id="main-view-container">%s</div></div>',
            $this->Flash->render()
        );
    }
    $formEnd = $this->Form->end();
    if (!empty($ajax)) {
        echo $this->element('genericElements/genericModal', array(
            'title' => empty($data['title']) ? h(\Cake\Utility\Inflector::humanize($this->request->params['action'])) . ' ' . $modelForForm : h($data['title']),
            'body' => sprintf(
                '%s%s%s%s%s%s',
                empty($data['description']) ? '' : sprintf(
                    '<div class="pb-2">%s</div>',
                    $data['description']
                ),
                $ajaxFlashMessage,
                $formCreate,
                $fieldsString,
                $formEnd,
                $metaFieldString
            ),
            'actionButton' => $this->element('genericElements/Form/submitButton', $submitButtonData),
            'class' => 'modal-lg'
        ));
    } else {
        echo sprintf(
            '%s<h2>%s</h2>%s%s%s%s%s%s%s%s',
            empty($ajax) ? '<div class="col-8">' : '',
            empty($data['title']) ? h(\Cake\Utility\Inflector::humanize($this->request->params['action'])) . ' ' . $modelForForm : h($data['title']),
            $formCreate,
            $ajaxFlashMessage,
            empty($data['description']) ? '' : sprintf(
                '<div class="pb-3">%s</div>',
                $data['description']
            ),
            $fieldsString,
            $this->element('genericElements/Form/submitButton', $submitButtonData),
            $formEnd,
            $metaFieldString,
            empty($ajax) ? '</div>' : ''
        );
    }
?>
