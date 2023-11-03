<?php
    $data = $this->Hash->extract($row, $field['data_path'])[0];
    $status_levels = $field['status_levels'];
    echo sprintf(
        '<i class="text-%s fas fa-%s" title="%s"></i>',
        h($field['status_levels'][$data]['colour']),
        empty($field['status_levels'][$data]['icon']) ? 'circle' : h($field['status_levels'][$data]['icon']),
        h($field['status_levels'][$data]['message'])
    );
?>
