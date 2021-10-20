<?php
    $controlParams = [
        'options' => $fieldData['options'],
        'empty' => $fieldData['empty'] ?? false,
        'value' => $fieldData['value'] ?? [],
        'multiple' => $fieldData['multiple'] ?? false,
        'disabled' => $fieldData['disabled'] ?? false,
        'class' => ($fieldData['class'] ?? '') . ' formDropdown form-select'
    ];
    echo $this->FormFieldMassage->prepareFormElement($this->Form, $controlParams, $fieldData);
