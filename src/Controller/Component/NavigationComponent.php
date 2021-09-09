<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\Routing\Router;

class NavigationComponent extends Component
{
    private $user = null;
    public $breadcrumb = null;

    public function initialize(array $config): void
    {
        $this->request = $config['request'];
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
        $controller = Inflector::underscore($this->request->getParam('controller'));
        $action = Inflector::underscore($this->request->getParam('action'));
        if (empty($this->fullBreadcrumb[$controller]['routes']["{$controller}:{$action}"])) {
            return []; // no breadcrumb defined for this endpoint
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

    public function genBreadcrumb(): array
    {
        $fullConfig = [
            'broods' => [
                'defaults' => [
                    'depth-1' => [
                        'after' => 'broods:index',
                        'textGetter' => 'name',
                        'links' => [
                            'broods:view',
                            'broods:edit',
                            'local_tools:brood_tools',
                        ],
                        'actions' => [
                            'broods:delete',
                        ],
                    ]
                ],
                'routes' => [
                    'broods:index' => [
                        'label' => __('Broods'),
                        'icon' => 'network-wired',
                        'url' => '/broods/index',
                    ],
                    'broods:view' => [
                        'label' => __('View'),
                        'inherit' => 'depth-1',
                        'url' => '/broods/view/{{id}}',
                        'url_vars' => ['id' => 'id'],
                    ],
                    'broods:edit' => [
                        'label' => __('Edit'),
                        'inherit' => 'depth-1',
                        'url' => '/broods/edit/{{id}}',
                        'url_vars' => ['id' => 'id'],
                    ],
                    'broods:delete' => [
                        'label' => __('Delete'),
                        'inherit' => 'depth-1',
                        'url' => '/broods/delete/{{id}}',
                        'url_vars' => ['id' => 'id'],
                    ],
                ]
            ],
            'local_tools' => [
                'routes' => [
                    'local_tools:brood_tools' => [
                        'label' => __('Brood Tools'),
                        'icon' => 'tools',
                        'url' => '/localTools/broodTools/{{id}}',
                        'url_vars' => ['id' => 'id'],
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


        // return [
        //     'Broods' => [
        //         'index' => [
        //             'label' => 'Broods',
        //             'icon' => 'network-wired',
        //             'url' => ['controller' => 'Broods', 'action' => 'index'],
        //             'children' => [
        //                 'view' => [
        //                     'textGetter' => 'name',
        //                     'url' => ['controller' => 'Broods', 'action' => 'view', 'argsGetter' => ['id']],
        //                     'links' => [
        //                         'view' => [
        //                             'label' => __('View'),
        //                             'url' => ['controller' => 'Broods', 'action' => 'view', 'argsGetter' => ['id']],
        //                         ],
        //                         'local_tools' => [
        //                             'textGetter' => 'name',
        //                             'url' => ['controller' => 'Broods', 'action' => 'delete', 'argsGetter' => ['id']],
        //                         ],
        //                     ],
        //                     'actions' => [
        //                         'edit' => [
        //                             'label' => __('Edit'),
        //                             'url' => ['controller' => 'Broods', 'action' => 'edit', 'argsGetter' => ['id']],
        //                         ],
        //                         'delete' => [
        //                             'label' => __('Delete'),
        //                             'url' => ['controller' => 'Broods', 'action' => 'delete', 'argsGetter' => ['id']],
        //                         ],
        //                     ],
        //                 ],
        //             ],
        //         ],
        //     ],
        // ];
    }
}