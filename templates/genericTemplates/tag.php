<?php
    echo $this->Tag->tags([
        'allTags' => $allTags,
        'tags' => $entity->tags,
        'picker' => true,
        'editable' => true,
    ]);
