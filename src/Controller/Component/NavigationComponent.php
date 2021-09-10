<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use Cake\Routing\Router;
use Cake\ORM\TableRegistry;

class NavigationComponent extends Component
{
    private $user = null;
    public $breadcrumb = null;
    public $iconToTableMapping = [
        'Individuals' => 'address-book',
        'Organisations' => 'building',
        'EncryptionKeys' => 'key',
        'SharingGroups' => 'user-friends',
        'Broods' => 'network-wired',
        'Roles' => 'id-badge',
        'Users' => 'users',
        'Inbox' => 'inbox',
        'Outbox' => 'inbox',
        'MetaTemplates' => 'object-group',
        'LocalTools' => 'tools',
        'Instance' => 'server',
    ];

    public function initialize(array $config): void
    {
        $this->request = $config['request'];
    }

    public function beforeFilter($event)
    {
        $this->fullBreadcrumb = $this->genBreadcrumb();
        $this->breadcrumb = $this->getBreadcrumb();
    }

    public function getSideMenu(): array
    {
        return [
            'ContactDB' => [
                'Individuals' => [
                    'label' => __('Individuals'),
                    'icon' => 'address-book',
                    'url' => '/individuals/index',
                ],
                'Organisations' => [
                    'label' => __('Organisations'),
                    'icon' => 'building',
                    'url' => '/organisations/index',
                ],
                'EncryptionKeys' => [
                    'label' => __('Encryption keys'),
                    'icon' => 'key',
                    'url' => '/encryptionKeys/index',
                ]
            ],
            'Trust Circles' => [
                'SharingGroups' => [
                    'label' => __('Sharing Groups'),
                    'icon' => 'user-friends',
                    'url' => '/sharingGroups/index',
                ]
            ],
            'Sync' => [
                'Broods' => [
                    'label' => __('Broods'),
                    'icon' => 'network-wired',
                    'url' => '/broods/index',
                ]
            ],
            'Administration' => [
                'Roles' => [
                    'label' => __('Roles'),
                    'icon' => 'id-badge',
                    'url' => '/roles/index',
                ],
                'Users' => [
                    'label' => __('Users'),
                    'icon' => 'users',
                    'url' => '/users/index',
                ],
                'Messages' => [
                    'label' => __('Messages'),
                    'icon' => 'inbox',
                    'url' => '/inbox/index',
                    'children' => [
                        'index' => [
                            'url' => '/inbox/index',
                            'label' => __('Inbox')
                        ],
                        'outbox' => [
                            'url' => '/outbox/index',
                            'label' => __('Outbox')
                        ],
                    ]
                ],
                'Add-ons' => [
                    'label' => __('Add-ons'),
                    'icon' => 'puzzle-piece',
                    'children' => [
                        'MetaTemplates.index' => [
                            'label' => __('Meta Field Templates'),
                            'icon' => 'object-group',
                            'url' => '/metaTemplates/index',
                        ],
                        'LocalTools.index' => [
                            'label' => __('Local Tools'),
                            'icon' => 'tools',
                            'url' => '/localTools/index',
                        ]
                    ]
                ],
                'Instance' => [
                    'label' => __('Instance'),
                    'icon' => 'server',
                    'children' => [
                        'Database' => [
                            'label' => __('Database'),
                            'url' => '/instance/migrationIndex',
                            'icon' => 'database',
                        ]
                    ]
                ],
            ],
            'Open' => [
                'Organisations' => [
                    'label' => __('Organisations'),
                    'icon' => 'buildings',
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
                    'icon' => 'address-book',
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

    public function getBreadcrumb(): array
    {
        $controller = $this->request->getParam('controller');
        $action = $this->request->getParam('action');
        if (empty($this->fullBreadcrumb[$controller]['routes']["{$controller}:{$action}"])) {
            return [[
                'label' => $controller,
                'url' => Router::url(['controller' => $controller, 'action' => $action]),
            ]]; // no breadcrumb defined for this endpoint
        }
        $currentRoute = $this->fullBreadcrumb[$controller]['routes']["{$controller}:{$action}"];
        $breadcrumbPath = $this->getBreadcrumbPath("{$controller}:{$action}", $currentRoute);
        return $breadcrumbPath['objects'];
    }

    public function getBreadcrumbPath(string $startRoute, array $currentRoute): array
    {
        $route = $startRoute;
        $path = [
            'routes' => [],
            'objects' => [],
        ];
        $visited = [];
        while (empty($visited[$route])) {
            $visited[$route] = true;
            $path['routes'][] = $route;
            $path['objects'][] = $currentRoute;
            if (!empty($currentRoute['after'])) {
                $route = $currentRoute['after'];
                $split = explode(':', $currentRoute['after']);
                $currentRoute = $this->fullBreadcrumb[$split[0]]['routes'][$currentRoute['after']];
            }
        }
        $path['routes'] = array_reverse($path['routes']);
        $path['objects'] = array_reverse($path['objects']);
        return $path;
    }

    private function insertInheritance(array $config, array $fullConfig): array
    {
        if (!empty($config['routes'])) {
            foreach ($config['routes'] as $routeName => $value) {
                $config['routes'][$routeName]['route_path'] = $routeName;
                if (!empty($value['inherit'])) {
                    $default = $config['defaults'][$value['inherit']] ?? [];
                    $config['routes'][$routeName] = array_merge($config['routes'][$routeName], $default);
                    unset($config['routes'][$routeName]['inherit']);
                }
            }
        }
        return $config;
    }

    private function insertRelated(array $config, array $fullConfig): array
    {
        if (!empty($config['routes'])) {
            foreach ($config['routes'] as $routeName => $value) {
                if (!empty($value['links'])) {
                    foreach ($value['links'] as $i => $linkedRoute) {
                        $split = explode(':', $linkedRoute);
                        if (!empty($fullConfig[$split[0]]['routes'][$linkedRoute])) {
                            $linkedRouteObject = $fullConfig[$split[0]]['routes'][$linkedRoute];
                            if (!empty($linkedRouteObject)) {
                                $config['routes'][$routeName]['links'][$i] = $linkedRouteObject;
                                continue;
                            }
                        }
                        unset($config['routes'][$routeName]['links'][$i]);
                    }
                }
                if (!empty($value['actions'])) {
                    foreach ($value['actions'] as $i => $linkedRoute) {
                        $split = explode(':', $linkedRoute);
                        if (!empty($fullConfig[$split[0]]['routes'][$linkedRoute])) {
                            $linkedRouteObject = $fullConfig[$split[0]]['routes'][$linkedRoute];
                            if (!empty($linkedRouteObject)) {
                                $config['routes'][$routeName]['actions'][$i] = $linkedRouteObject;
                                continue;
                            }
                        }
                        unset($config['routes'][$routeName]['actions'][$i]);
                    }
                }
            }
        }
        return $config;
    }

    public function getDefaultCRUDConfig(string $controller, array $overrides=[], array $merges=[]): array
    {
        $table = TableRegistry::getTableLocator()->get($controller);
        $default = [
            'defaults' => [
                'depth-1' => [
                    'after' => "{$controller}:index",
                    'textGetter' => !empty($table->getDisplayField()) ? $table->getDisplayField() : 'id',
                    'links' => [
                        "{$controller}:view",
                        "{$controller}:edit",
                    ],
                    'actions' => [
                        "{$controller}:delete",
                    ],
                ]
            ],
            'routes' => [
                "{$controller}:index" => [
                    'label' => Inflector::humanize($controller),
                    'url' => "/{$controller}/index",
                    'icon' => $this->iconToTableMapping[$controller]
                ],
                "{$controller}:view" => [
                    'label' => __('View'),
                    'inherit' => 'depth-1',
                    'url' => "/{$controller}/view/{{id}}",
                    'url_vars' => ['id' => 'id'],
                ],
                "{$controller}:edit" => [
                    'label' => __('Edit'),
                    'inherit' => 'depth-1',
                    'url' => "/{$controller}/edit/{{id}}",
                    'url_vars' => ['id' => 'id'],
                ],
                "{$controller}:delete" => [
                    'label' => __('Delete'),
                    'inherit' => 'depth-1',
                    'url' => "/{$controller}/delete/{{id}}",
                    'url_vars' => ['id' => 'id'],
                ],
            ]
        ];
        $merged = array_merge_recursive($default, $merges);
        $overridden = array_replace_recursive($merged, $overrides);
        return $overridden;
    }

    public function genBreadcrumb(): array
    {
        $fullConfig = [
            'Individuals' => $this->getDefaultCRUDConfig('Individuals'),
            'Organisations' => $this->getDefaultCRUDConfig('Organisations'),
            'EncryptionKeys' => $this->getDefaultCRUDConfig('EncryptionKeys'),
            'SharingGroups' => $this->getDefaultCRUDConfig('SharingGroups'),
            'Broods' => $this->getDefaultCRUDConfig('Broods', [], [
                'defaults' => ['depth-1' => ['links' => 'LocalTools:brood_tools']]
            ]),
            'Roles' => $this->getDefaultCRUDConfig('Roles'),
            'Users' => $this->getDefaultCRUDConfig('Users'),
            'Inbox' => $this->getDefaultCRUDConfig('Inbox', [
                'defaults' => ['depth-1' => [
                    'links' => ['Inbox:view', 'Inbox:process'],
                    'actions' => ['Inbox:process', 'Inbox:delete'],
                ]]
            ], [
                'routes' => [
                    'Inbox:discard' => [
                        'label' => __('Discard request'),
                        'inherit' => 'depth-1',
                        'url' => '/inbox/discard/{{id}}',
                        'url_vars' => ['id' => 'id'],
                    ],
                    'Inbox:process' => [
                        'label' => __('Process request'),
                        'inherit' => 'depth-1',
                        'url' => '/inbox/process/{{id}}',
                        'url_vars' => ['id' => 'id'],
                    ],
                ]
            ]),
            'Outbox' => $this->getDefaultCRUDConfig('Outbox', [
                'defaults' => ['depth-1' => [
                    'links' => ['Outbox:view', 'Outbox:process'],
                    'actions' => ['Outbox:process', 'Outbox:delete'],
                ]]
            ], [
                'routes' => [
                    'Outbox:discard' => [
                        'label' => __('Discard request'),
                        'inherit' => 'depth-1',
                        'url' => '/outbox/discard/{{id}}',
                        'url_vars' => ['id' => 'id'],
                    ],
                    'Outbox:process' => [
                        'label' => __('Process request'),
                        'inherit' => 'depth-1',
                        'url' => '/outbox/process/{{id}}',
                        'url_vars' => ['id' => 'id'],
                    ],
                ]
            ]),
            'MetaTemplates' => $this->getDefaultCRUDConfig('MetaTemplates', [
                'defaults' => ['depth-1' => [
                    'links' => ['MetaTemplates:view', ''], // '' to remove leftovers. Related to https://www.php.net/manual/en/function.array-replace-recursive.php#124705
                    'actions' => ['MetaTemplates:toggle'],
                ]]
            ], [
                'routes' => [
                    'MetaTemplates:toggle' => [
                        'label' => __('Toggle Meta-template'),
                        'inherit' => 'depth-1',
                        'url' => '/MetaTemplates/toggle/{{id}}',
                        'url_vars' => ['id' => 'id'],
                    ],
                ]
            ]),
            'LocalTools' => [
                'routes' => [
                    'LocalTools:index' => [
                        'label' => __('Local Tools'),
                        'url' => '/localTools/index',
                        'icon' => $this->iconToTableMapping['LocalTools'],
                    ],
                    'LocalTools:viewConnector' => [
                        'label' => __('View'),
                        'textGetter' => 'name',
                        'url' => '/localTools/viewConnector/{{connector}}',
                        'url_vars' => ['connector' => 'connector'],
                        'after' => 'LocalTools:index',
                    ],
                    'LocalTools:broodTools' => [
                        'label' => __('Brood Tools'),
                        'url' => '/localTools/broodTools/{{id}}',
                        'url_vars' => ['id' => 'id'],
                    ],
                ]
            ],
            'Instance' => [
                'routes' => [
                    'Instance:migrationIndex' => [
                        'label' => __('Database Migration'),
                        'url' => '/instance/migrationIndex',
                        'icon' => 'database'
                    ]
                ]
            ]
        ];
        foreach ($fullConfig as $controller => $config) {
            $fullConfig[$controller] = $this->insertInheritance($config, $fullConfig);
        }
        foreach ($fullConfig as $controller => $config) {
            $fullConfig[$controller] = $this->insertRelated($config, $fullConfig);
        }
        return $fullConfig;
    }
}