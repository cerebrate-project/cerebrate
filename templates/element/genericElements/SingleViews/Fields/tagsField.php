<?php
// $tags = Cake\Utility\Hash::extract($data, $field['path']);
$tagList = Cake\Utility\Hash::get($data, 'tag_list');
$allTags = [
    ['id' => 'tlp:red', 'text' => 'tlp:red', 'colour' => 'red'],
    ['id' => 'tlp:green', 'text' => 'tlp:green', 'colour' => 'green'],
    ['id' => 'tlp:amber', 'text' => 'tlp:amber', 'colour' => '#983965'],
    ['id' => 'tlp:white', 'text' => 'tlp:white', 'colour' => 'white'],
];
echo $this->Tag->tags([
    'allTags' => $allTags,
    'tags' => $tagList,
    'picker' => true,
    'editable' => true,
]);
