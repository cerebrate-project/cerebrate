<?php
    // $randomVal = Cake\Utility\Security::randomString(8);
    // $params['type'] = 'textarea';
    // $textareaClass = "area-for-codemirror-{$randomVal}";
    // $params['class'] = [$textareaClass];
    // echo $this->FormFieldMassage->prepareFormElement($this->Form, $params, $fieldData);
    echo $this->element('genericElements/codemirror', [
        'data' => $fieldData,
        'params' => $params,
    ]);
