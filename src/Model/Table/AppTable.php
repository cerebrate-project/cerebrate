<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;

class AppTable extends Table
{
    public function initialize(array $config): void
    {
    }

    public function getMenu()
    {
        $open = Configure::read('Cerebrate.open');
        return [
            'ContactDB' => [
                'Individuals' => [
                    'label' => __('Individuals'),
                    'url' => '/individuals/index',
                    'children' => [
                        'index' => [
                            'url' => '/individuals/index',
                            'label' => __('List individuals')
                        ],
                        'add' => [
                            'url' => '/individuals/add',
                            'label' => __('Add individual')
                        ],
                        'view' => [
                            'url' => '/individuals/view/{{id}}',
                            'label' => __('View individual'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1
                        ],
                        'edit' => [
                            'url' => '/individuals/edit/{{id}}',
                            'label' => __('Edit individual'),
                            'actions' => ['edit', 'delete', 'view'],
                            'skipTopMenu' => 1
                        ],
                        'delete' => [
                            'url' => '/individuals/delete/{{id}}',
                            'label' => __('Delete individual'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1
                        ]
                    ]
                ],
                'Organisations' => [
                    'label' => __('Organisations'),
                    'url' => '/organisations/index',
                    'children' => [
                        'index' => [
                            'url' => '/organisations/index',
                            'label' => __('List organisations')
                        ],
                        'add' => [
                            'url' => '/organisations/add',
                            'label' => __('Add organisation')
                        ],
                        'view' => [
                            'url' => '/organisations/view/{{id}}',
                            'label' => __('View organisation'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1
                        ],
                        'edit' => [
                            'url' => '/organisations/edit/{{id}}',
                            'label' => __('Edit organisation'),
                            'actions' => ['edit', 'delete', 'view'],
                            'skipTopMenu' => 1
                        ],
                        'delete' => [
                            'url' => '/organisations/delete/{{id}}',
                            'label' => __('Delete organisation'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1
                        ]
                    ]
                ],
                'EncryptionKeys' => [
                    'label' => __('Encryption keys'),
                    'url' => '/encryptionKeys/index',
                    'children' => [
                        'index' => [
                            'url' => '/encryptionKeys/index',
                            'label' => __('List encryption keys')
                        ],
                        'add' => [
                            'url' => '/encryptionKeys/add',
                            'label' => __('Add encryption key')
                        ],
                        'edit' => [
                            'url' => '/encryptionKeys/edit/{{id}}',
                            'label' => __('Edit organisation'),
                            'actions' => ['edit'],
                            'skipTopMenu' => 1
                        ]
                    ]
                ]
            ],
            'Administration' => [
                'Roles' => [
                    'label' => __('Roles'),
                    'url' => '/roles/index',
                    'children' => [
                        'index' => [
                            'url' => '/roles/index',
                            'label' => __('List roles')
                        ],
                        'add' => [
                            'url' => '/roles/add',
                            'label' => __('Add role')
                        ],
                        'view' => [
                            'url' => '/roles/view/{{id}}',
                            'label' => __('View role'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1
                        ],
                        'edit' => [
                            'url' => '/roles/edit/{{id}}',
                            'label' => __('Edit role'),
                            'actions' => ['edit', 'delete', 'view'],
                            'skipTopMenu' => 1
                        ],
                        'delete' => [
                            'url' => '/roles/delete/{{id}}',
                            'label' => __('Delete role'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1
                        ]
                    ]
                ],
                'Users' => [
                    'label' => __('Users'),
                    'url' => '/users/index',
                    'children' => [
                        'index' => [
                            'url' => '/users/index',
                            'label' => __('List users')
                        ],
                        'add' => [
                            'url' => '/users/add',
                            'label' => __('Add user')
                        ],
                        'view' => [
                            'url' => '/users/view/{{id}}',
                            'label' => __('View user'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1
                        ],
                        'edit' => [
                            'url' => '/users/edit/{{id}}',
                            'label' => __('Edit user'),
                            'actions' => ['edit', 'delete', 'view'],
                            'skipTopMenu' => 1
                        ],
                        'delete' => [
                            'url' => '/users/delete/{{id}}',
                            'label' => __('Delete user'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1
                        ]
                    ]
                ]
            ],
            'Cerebrate' => [
                'Roles' => [
                    'label' => __('Roles'),
                    'url' => '/roles/index',
                    'children' => [
                        'index' => [
                            'url' => '/roles/index',
                            'label' => __('List roles')
                        ],
                        'view' => [
                            'url' => '/roles/view/{{id}}',
                            'label' => __('View role'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1
                        ]
                    ]
                ],
                'Instance' => [
                    __('Instance'),
                    'url' => '/instance/home',
                    'children' => [
                        'home' => [
                            'url' => '/instance/home',
                            'label' => __('Home')
                        ]
                    ]
                ]
            ],
            'Open' => [
                'Organisations' => [
                    'label' => __('Organisations'),
                    'url' => '/open/organisations/index',
                    'children' => [
                        'index' => [
                            'url' => '/open/organisations/index',
                            'label' => __('List organisations')
                        ],
                    ],
                    'open' => in_array('organisations', Configure::read('Cerebrate.open'))
                ],
                'Individuals' => [
                    'label' => __('Individuals'),
                    'url' => '/open/individuals/index',
                    'children' => [
                        'index' => [
                            'url' => '/open/individuals/index',
                            'label' => __('List individuals')
                        ],
                    ],
                    'open' => in_array('individuals', Configure::read('Cerebrate.open'))
                ]
            ]
        ];
    }
}
