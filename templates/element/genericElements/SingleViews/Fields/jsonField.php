<?php
$value = Cake\Utility\Hash::extract($data, $field['path']);
echo sprintf('<pre><code>%s</pre></code>', h(json_encode($value, JSON_PRETTY_PRINT)));
