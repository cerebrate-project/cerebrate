<?php
    $controlParams = [
        'options' => $fieldData['options'],
        'empty' => $fieldData['empty'] ?? false,
        'value' => $fieldData['value'] ?? null,
        'multiple' => $fieldData['multiple'] ?? false,
        'disabled' => $fieldData['disabled'] ?? false,
        'class' => ($fieldData['class'] ?? '') . ' formDropdown form-select',
        'default' => ($fieldData['default'] ?? null)
    ];
    echo $this->FormFieldMassage->prepareFormElement($this->Form, $controlParams, $fieldData);
