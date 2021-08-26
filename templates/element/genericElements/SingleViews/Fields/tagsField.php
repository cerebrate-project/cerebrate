<?php
// $tags = Cake\Utility\Hash::extract($data, $field['path']);
$tags = Cake\Utility\Hash::get($data, 'tags');
echo $this->Tag->tags([
    'allTags' => $allTags,
    'tags' => $tags,
    'picker' => true,
    'editable' => true,
]);
