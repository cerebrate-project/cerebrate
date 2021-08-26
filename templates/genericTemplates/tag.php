<?php
    $allTags = [
        ['id' => 'tlp:red', 'text' => 'tlp:red', 'colour' => 'red'],
        ['id' => 'tlp:green', 'text' => 'tlp:green', 'colour' => 'green'],
        ['id' => 'tlp:amber', 'text' => 'tlp:amber', 'colour' => '#983965'],
        ['id' => 'tlp:white', 'text' => 'tlp:white', 'colour' => 'white'],
    ];
    echo $this->Tag->tags([
        'allTags' => $allTags,
        'tags' => $entity->tag_list,
        'picker' => true,
        'editable' => true,
    ]);
