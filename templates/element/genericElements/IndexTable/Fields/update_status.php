<?
    if (empty($field['data_path']) || empty($row[$field['data_path']])) {
        return '';
    }
    $status = $row[$field['data_path']];

    $icon = !empty($row['status']['updateable']) ? 'check' : 'times';
    echo $this->Bootstrap->icon($icon);
?>