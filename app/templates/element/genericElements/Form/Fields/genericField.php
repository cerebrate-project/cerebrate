<?php
    $params['div'] = false;
    $params['class'] .= ' form-control';
    $temp = $form->control($fieldData['field'], $params);
    if (!empty($fieldData['hidden'])) {
        $temp = '<span class="hidden">' . $temp . '</span>';
    }
    echo $temp;
?>
