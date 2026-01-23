<?php
    echo $this->element('genericElements/IndexTable/Fields/country', ['field' => [
        'data_path' => $field['path'],
        'flag_small' => $field['flag_small'] ?? false,
    ], 'row' => $data]);
?>
