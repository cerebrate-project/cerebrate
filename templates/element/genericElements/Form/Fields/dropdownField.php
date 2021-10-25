<?php
    $controlParams = [
        'options' => $fieldData['options'],
        'empty' => $fieldData['empty'] ?? false,
        'value' => $fieldData['value'] ?? [],
        'multiple' => $fieldData['multiple'] ?? false,
        'disabled' => $fieldData['disabled'] ?? false,
        'class' => ($fieldData['class'] ?? '') . ' formDropdown form-select'
    ];
    if (!empty($fieldData['label'])) {
        $controlParams['label'] = $fieldData['label'];
    }
    echo $this->FormFieldMassage->prepareFormElement($this->Form, $controlParams, $fieldData);
