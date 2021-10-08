<?php
echo $this->Bootstrap->button([
    'nodeType' => 'a',
    'icon' => 'plus',
    'title' => __('Add new bookmark'),
    'variant' => 'primary',
    'size' => 'sm',
    'params' => [
        'id' => 'btn-add-bookmark',
    ]
]);
