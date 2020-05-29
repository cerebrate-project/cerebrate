<?php
    $params['div'] = false;
    $temp = $form->control($fieldData['field'], $params);
    if (!empty($fieldData['hidden'])) {
        $temp = '<span class="hidden">' . $temp . '</span>';
    }
    echo $temp;
?>
