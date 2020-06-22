<?php
    $groups = '';
    foreach ($data['children'] as $group) {
        $groups .= $this->element('/genericElements/ListTopBar/group_' . (empty($group['type']) ? 'simple' : h($group['type'])), array('data' => $group, 'tableRandomValue' => $tableRandomValue));
    }
    $tempClass = "btn-toolbar";
    if (count($data['children']) > 1) {
        $tempClass .= ' justify-content-between';
    } else if (!empty($data['pull'])) {
        $tempClass .= ' float-' . h($data['pull']);
    }
    echo sprintf(
        '<div class="%s" role="toolbar" aria-label="Index toolbar">%s</div>',
        $tempClass,
        $groups
    );
?>
