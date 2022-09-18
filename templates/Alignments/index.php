<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'children' => [

            ],
        ],
        'fields' => [
            [
                'name' => '#',
                'sort' => 'id',
                'data_path' => 'id',
            ],
            [
                'name' => __('Individual'),
                'data_path' => 'individual.email',
                'url' => '/individuals/view/{{0}}',
                'url_vars' => ['individual.id']
            ],
            [
                'name' => __('Organisation'),
                'data_path' => 'organisation.name',
                'url' => '/organisations/view/{{0}}',
                'url_vars' => ['organisation.id']
            ],
            [
                'name' => __('Type'),
                'sort' => 'type',
                'data_path' => 'type'
            ],
        ],
        'title' => __('User index'),
        'description' => __('The list of enrolled users in this Cerebrate instance. All of the users have or at one point had access to the system.'),
        'pull' => 'right',
        'actions' => [

        ]
    ]
]);
echo '</div>';
?>
