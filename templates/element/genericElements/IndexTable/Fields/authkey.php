<?php
    $start = $this->Hash->extract($row, 'authkey_start')[0];
    $end = $this->Hash->extract($row, 'authkey_end')[0];
    echo sprintf(
        '<div>%s: <span class="font-weight-bold text-info">%s</span></div>',
        __('Starts with'),
        h($start)
    );
    echo sprintf(
        '<div>%s: <span class="font-weight-bold text-info">%s</span></div>',
        __('Ends with'),
        h($end)
    );
?>
