<?php
    $data = $this->Hash->extract($row, $field['data_path']);
    $links = [];
    foreach ($data as $object) {
        $temp_id = h($this->Hash->extract($object, $field['data_id_sub_path'])[0]);
        $temp_value = h($this->Hash->extract($object, $field['data_value_sub_path'])[0]);
        $url = str_replace('{{data_id}}', $temp_id, $field['url_pattern']);
        $links[] = sprintf(
            '<a href="%s">%s</a>',
            $url,
            $temp_value
        );
    }
    echo implode('<br />', $links);
?>
