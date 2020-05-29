<?php
$value = Cake\Utility\Hash::extract($data, $field['path']);
echo empty($value[0]) ? '' : $value[0];
