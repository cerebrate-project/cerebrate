<?php
namespace SidemenuNavigation;

use Cake\Core\Configure;

class Sidemenu {
    private $iconTable;
    private $request;

    public function __construct($iconTable, $request)
    {
        $this->iconTable = $iconTable;
        $this->request = $request;
    }

    public function get(): array
    {
        return [
            __('ContactDB') => [
                'Individuals' => [
                    'label' => __('Individuals'),
                    'icon' => $this->iconTable['Individuals'],
                    'url' => '/individuals/index',
                ],
                'Organisations' => [
                    'label' => __('Organisations'),
                    'icon' => $this->iconTable['Organisations'],
                    'url' => '/organisations/index',
                ],
                'EncryptionKeys' => [
                    'label' => __('Encryption keys'),
                    'icon' => $this->iconTable['EncryptionKeys'],
                    'url' => '/encryptionKeys/index',
                ]
            ],
            __('Trust Circles') => [
                'SharingGroups' => [
                    'label' => __('Sharing Groups'),
                    'icon' => $this->iconTable['SharingGroups'],
                    'url' => '/sharingGroups/index',
                ],
                'MailingLists' => [
                    'label' => __('Mailing Lists'),
                    'icon' => $this->iconTable['MailingLists'],
                    'url' => '/mailingLists/index',
                ]
            ],
            __('Synchronisation') => [
                'Broods' => [
                    'label' => __('Broods'),
                    'icon' => $this->iconTable['Broods'],
                    'url' => '/broods/index',
                ],
            ],
            __('Administration') => [
                'Roles' => [
                    'label' => __('Roles'),
                    'icon' => $this->iconTable['Roles'],
                    'url' => '/roles/index',
                ],
                'Users' => [
                    'label' => __('Users'),
                    'icon' => $this->iconTable['Users'],
                    'url' => '/users/index',
                ],
                'UserSettings' => [
                    'label' => __('Users Settings'),
                    'icon' => $this->iconTable['UserSettings'],
                    'url' => '/user-settings/index',
                ],
                'LocalTools.index' => [
                    'label' => __('Local Tools'),
                    'icon' => $this->iconTable['LocalTools'],
                    'url' => '/localTools/index',
                ],
                'Messages' => [
                    'label' => __('Messages'),
                    'icon' => $this->iconTable['Inbox'],
                    'url' => '/inbox/index',
                    'children' => [
                        'inbox' => [
                            'url' => '/inbox/index',
                            'label' => __('Inbox'),
                        ],
                        'outbox' => [
                            'url' => '/outbox/index',
                            'label' => __('Outbox'),
                        ],
                    ]
                ],
                'Add-ons' => [
                    'label' => __('Add-ons'),
                    'icon' => 'puzzle-piece',
                    'children' => [
                        'MetaTemplates.index' => [
                            'label' => __('Meta Field Templates'),
                            'icon' => $this->iconTable['MetaTemplates'],
                            'url' => '/metaTemplates/index',
                        ],
                        'Tags.index' => [
                            'label' => __('Tags'),
                            'icon' => $this->iconTable['Tags'],
                            'url' => '/tags/index',
                        ],
                    ]
                ],
                'Instance' => [
                    'label' => __('Instance'),
                    'icon' => $this->iconTable['Instance'],
                    'children' => [
                        'Settings' => [
                            'label' => __('Settings'),
                            'url' => '/instance/settings',
                            'icon' => 'cogs',
                        ],
                        'Database' => [
                            'label' => __('Database'),
                            'url' => '/instance/migrationIndex',
                            'icon' => 'database',
                        ],
                        'AuditLogs' => [
                            'label' => __('Audit Logs'),
                            'url' => '/auditLogs/index',
                            'icon' => 'history',
                        ],
                        'PermissionLimitations' => [
                            'label' => __('Permission Limitations'),
                            'url' => '/permissionLimitations/index',
                            'icon' => 'jedi',
                        ],
                        'Enumerations' => [
                            'label' => __('Collections'),
                            'url' => '/enumerationCollections/index',
                            'icon' => 'list',
                        ],
                    ]
                ],
                'API' => [
                    'label' => __('API'),
                    'icon' => $this->iconTable['API'],
                    'url' => '/api/index',
                ],
            ],
            'Open' => [
                'Organisations' => [
                    'label' => __('Organisations'),
                    'icon' => $this->iconTable['Organisations'],
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
                    'icon' => $this->iconTable['Individuals'],
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
