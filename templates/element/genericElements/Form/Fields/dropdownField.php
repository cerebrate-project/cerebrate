<?php
    $controlParams = [
        'options' => $fieldData['options'],
        'empty' => $fieldData['empty'] ?? false,
        'class' => ($fieldData['class'] ?? '') . ' formDropdown form-select'
    ];
    echo $this->FormFieldMassage->prepareFormElement($this->Form, $controlParams, $fieldData);
