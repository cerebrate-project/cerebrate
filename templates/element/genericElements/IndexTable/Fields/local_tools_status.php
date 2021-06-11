<?php
    $tools = $this->Hash->extract($row, 'local_tools');
    $output = [];
    foreach ($tools as $tool) {
        $output[] = sprintf(
            '<a href="/localTools/view/%s">%s</a>: %s',
            h($tool['id']),
            h($tool['name']),
            h($tool['status'])
        );
    }
    echo implode('<br />', $output);
?>
