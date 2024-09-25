<?php
    $country = $this->Hash->get($row, $field['data_path']);
    $small = !empty($field['flag_small']);
    $html = '';
    if (!is_null($country)) {
        $html .= $this->Flag->flag($country, $small);
    }
    echo $html;
?>
