<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use App\Model\Entity\User;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Utility\Inflector;

class ACLComponent extends Component
{

    private $user = null;

    public function initialize(array $config): void
    {
        $this->request = $config['request'];
        $this->Authentication = $config['Authentication'];
    }

    // syntax:
    // $__aclList[$controller][$action] = $permission_rules
    // $controller == '*'                 -  any controller can have this action
    // $action == []                 -  site admin only has access
    // $action == '*'                     -  any role has access
    // $action == array('OR' => [])  -  any role in the array has access
    // $action == array('AND' => []) -  roles with all permissions in the array have access
    // If we add any new functionality to MISP and we don't add it to this list, it will only be visible to site admins.
    private $aclList = array(
        '*' => [
            'checkPermission' => ['*'],
            'generateUUID' => ['*'],
            'queryACL' => ['perm_admin']
        ],
        'Alignments' => [
            'add' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'index' => ['*'],
            'view' => ['*']
        ],
        'AuthKeys' => [
            'add' => ['*'],
            'delete' => ['*'],
            'index' => ['*']
        ],
        'Broods' => [
            'add' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'edit' => ['perm_admin'],
            'index' => ['perm_admin'],
            'view' => ['perm_admin']
        ],
        'EncryptionKeys' => [
            'add' => ['*'],
            'delete' => ['*'],
            'index' => ['*']
        ],
        'Individuals' => [
            'add' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'edit' => ['perm_admin'],
            'index' => ['*'],
            'view' => ['*']
        ],
        'Instance' => [
            'home' => ['*'],
            'status' => ['*']
        ],
        'MetaTemplateFields' => [
            'index' => ['perm_admin']
        ],
        'MetaTemplates' => [
            'disable' => ['perm_admin'],
            'enable' => ['perm_admin'],
            'index' => ['perm_admin'],
            'update' => ['perm_admin'],
            'view' => ['perm_admin']
        ],
        'Organisations' => [
            'add' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'edit' => ['perm_admin'],
            'index' => ['*'],
            'view' => ['*']
        ],
        'Pages' => [
            'display' => ['*']
        ],
        'Roles' => [
            'add' => ['perm_admin'],
            'delete' =>  ['perm_admin'],
            'edit' =>  ['perm_admin'],
            'index' =>  ['*'],
            'view' =>  ['*']
        ],
        'SharingGroups' => [
            'add' => ['perm_admin'],
            'addOrg' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'edit' => ['perm_admin'],
            'index' => ['*'],
            'listOrgs' => ['*'],
            'removeOrg' => ['perm_admin'],
            'view' => ['*']
        ],
        'Users' => [
            'add' => ['perm_admin'],
            'delete' => ['perm_admin'],
            'edit' => ['*'],
            'index' => ['perm_admin'],
            'login' => ['*'],
            'logout' => ['*'],
            'view' => ['*']
        ]
    );

    private function __checkLoggedActions($user, $controller, $action)
    {
        $loggedActions = array(
            'servers' => array(
                'index' => array(
                    'role' => array(
                        'NOT' => array(
                            'perm_site_admin'
                        )
                    ),
                    'message' => __('This could be an indication of an attempted privilege escalation on older vulnerable versions of MISP (<2.4.115)')
                )
            )
        );
        foreach ($loggedActions as $k => $v) {
            $loggedActions[$k] = array_change_key_case($v);
        }
        $message = '';
        if (!empty($loggedActions[$controller])) {
            if (!empty($loggedActions[$controller][$action])) {
                $message = $loggedActions[$controller][$action]['message'];
                $hit = false;
                if (empty($loggedActions[$controller][$action]['role'])) {
                    $hit = true;
                } else {
                    $role_req = $loggedActions[$controller][$action]['role'];
                    if (empty($role_req['OR']) && empty($role_req['AND']) && empty($role_req['NOT'])) {
                        $role_req = array('OR' => $role_req);
                    }
                    if (!empty($role_req['NOT'])) {
                        foreach ($role_req['NOT'] as $k => $v) {
                            if (!$user['Role'][$v]) {
                                $hit = true;
                                continue;
                            }
                        }
                    }
                    if (!$hit && !empty($role_req['AND'])) {
                        $subhit = true;
                        foreach ($role_req['AND'] as $k => $v) {
                            $subhit = $subhit && $user['Role'][$v];
                        }
                        if ($subhit) {
                            $hit = true;
                        }
                    }
                    if (!$hit && !empty($role_req['OR'])) {
                        foreach ($role_req['OR'] as $k => $v) {
                            if ($user['Role'][$v]) {
                                $hit = true;
                                continue;
                            }
                        }
                    }
                    if ($hit) {
                        $this->Log = TableRegistry::get('Log');
                        $this->Log->create();
                        $this->Log->save(array(
                                'org' => 'SYSTEM',
                                'model' => 'User',
                                'model_id' => $user['id'],
                                'email' => $user['email'],
                                'action' => 'security',
                                'user_id' => $user['id'],
                                'title' => __('User triggered security alert by attempting to access /%s/%s. Reason why this endpoint is of interest: %s', $controller, $action, $message),
                        ));
                    }
                }
            }
        }
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /*
     *  By default nothing besides the login is public. If configured, override the list with the additional interfaces
     */
    public function setPublicInterfaces(): void
    {
        $this->Authentication->allowUnauthenticated(['login']);
    }

    private function checkAccessInternal($controller, $action, $soft): bool
    {
        if (empty($this->user)) {
            // we have to be in a publically allowed scope otherwise the Auth component will kick us out anyway.
            return true;
        }
        if (!empty($this->user->role->perm_admin)) {
            return true;
        }
        //$this->__checkLoggedActions($user, $controller, $action);
        if (!isset($this->aclList[$controller])) {
            return $this->__error(404, __('Invalid controller.'), $soft);
        }
        if (isset($this->aclList[$controller][$action]) && !empty($this->aclList[$controller][$action])) {
            if (in_array('*', $this->aclList[$controller][$action])) {
                return true;
            }
            if (isset($this->aclList[$controller][$action]['OR'])) {
                foreach ($this->aclList[$controller][$action]['OR'] as $permission) {
                    if ($user['Role'][$permission]) {
                        return true;
                    }
                }
            } elseif (isset($this->aclList[$controller][$action]['AND'])) {
                $allConditionsMet = true;
                foreach ($this->aclList[$controller][$action]['AND'] as $permission) {
                    if (!$user['Role'][$permission]) {
                        $allConditionsMet = false;
                    }
                }
                if ($allConditionsMet) {
                    return true;
                }
            }
        }
        return false;
    }

    public function checkAccessUrl($url, $soft = false): bool
    {
        $urlParts = explode('/', $url);
        if ($urlParts[1] === 'open') {
            return in_array($urlParts[2], Configure::read('Cerebrate.open'));
        } else {
            return $this->checkAccessInternal(Inflector::camelize($urlParts[1]), $urlParts[2], $soft);
        }
    }

    // The check works like this:
    // If the user is a site admin, return true
    // If the requested action has an OR-d list, iterate through the list. If any of the permissions are set for the user, return true
    // If the requested action has an AND-ed list, iterate through the list. If any of the permissions for the user are not set, turn the check to false. Otherwise return true.
    // If the requested action has a permission, check if the user's role has it flagged. If yes, return true
    // If we fall through all of the checks, return an exception.
    public function checkAccess(bool $soft = false): bool
    {
        $controller = $this->request->getParam('controller');
        $action = $this->request->getParam('action');
        if ($this->checkAccessInternal($controller, $action, $soft) === true) {
            return true;
        }
        return $this->__error(403, 'You do not have permission to use this functionality.', $soft);
    }

    private function __error($code, $message, $soft = false)
    {
        if ($soft) {
            return false;
        }
        switch ($code) {
            case 404:
                throw new NotFoundException($message);
                break;
            case 403:
                throw new MethodNotAllowedException($message);
            default:
                throw new InternalErrorException('Unknown error: ' . $message);
        }
    }

    private function __findAllFunctions()
    {
        $functionFinder = '/public.function[\s\n]+(\S+)[\s\n]*\(/';
        $files = scandir(ROOT . '/src/Controller/');
        foreach ($files as $k => $file) {
            if (substr($file, -14) !== 'Controller.php') {
                unset($files[$k]);
            }
        }
        $results = [];
        foreach ($files as $file) {
            $controllerName = lcfirst(str_replace('Controller.php', "", $file));
            if ($controllerName === 'app') {
                $controllerName = '*';
            }
            $functionArray = [];
            $fileContents = file_get_contents(APP . 'Controller' . DS . $file);
            $fileContents = preg_replace('/\/\*[^\*]+?\*\//', '', $fileContents);
            preg_match_all($functionFinder, $fileContents, $functionArray);
            foreach ($functionArray[1] as $function) {
                if (substr($function, 0, 1) !== '_' && $function !== 'beforeFilter' && $function !== 'afterFilter') {
                    $results[$controllerName][] = $function;
                }
            }
        }
        return $results;
    }

    public function printAllFunctionNames($content = false)
    {
        $results = $this->__findAllFunctions();
        ksort($results);
        return $results;
    }

    public function findMissingFunctionNames($content = false)
    {
        $results = $this->__findAllFunctions();
        $missing = [];
        foreach ($results as $controller => $functions) {
            $controller = Inflector::camelize($controller);
            foreach ($functions as $function) {
                if (in_array($function, ['beforeFilter', 'beforeRender', 'initialize', 'afterFilter'])) {
                    continue;
                }
                if (!isset($this->aclList[$controller])
                || !in_array($function, array_keys($this->aclList[$controller]))) {
                    $missing[$controller][] = $function;
                }
            }
        }
        return $missing;
    }

    public function printRoleAccess($content = false)
    {
        $results = [];
        $this->Role = TableRegistry::get('Role');
        $conditions = [];
        if (is_numeric($content)) {
            $conditions = array('Role.id' => $content);
        }
        $roles = $this->Role->find('all', array(
            'recursive' => -1,
            'conditions' => $conditions
        ));
        if (empty($roles)) {
            throw new NotFoundException('Role not found.');
        }
        foreach ($roles as $role) {
            $urls = $this->__checkRoleAccess($role['Role']);
            $results[$role['Role']['id']] = array('name' => $role['Role']['name'], 'urls' => $urls);
        }
        return $results;
    }

    private function __checkRoleAccess($role)
    {
        $result = [];
        foreach ($this->__aclList as $controller => $actions) {
            $controllerNames = Inflector::variable($controller) == Inflector::underscore($controller) ? array(Inflector::variable($controller)) : array(Inflector::variable($controller), Inflector::underscore($controller));
            foreach ($controllerNames as $controllerName) {
                foreach ($actions as $action => $permissions) {
                    if ($role['perm_site_admin']) {
                        $result[] = DS . $controllerName . DS . $action;
                    } elseif (in_array('*', $permissions)) {
                        $result[] = DS . $controllerName . DS . $action . DS . '*';
                    } elseif (isset($permissions['OR'])) {
                        $access = false;
                        foreach ($permissions['OR'] as $permission) {
                            if ($role[$permission]) {
                                $access = true;
                            }
                        }
                        if ($access) {
                            $result[] = DS . $controllerName . DS . $action . DS . '*';
                        }
                    } elseif (isset($permissions['AND'])) {
                        $access = true;
                        foreach ($permissions['AND'] as $permission) {
                            if ($role[$permission]) {
                                $access = false;
                            }
                        }
                        if ($access) {
                            $result[] = DS . $controllerName . DS . $action . DS . '*';
                        }
                    } elseif (isset($permissions[0]) && $role[$permissions[0]]) {
                        $result[] = DS . $controllerName . DS . $action . DS . '*';
                    }
                }
            }
        }
        return $result;
    }

    public function getMenu()
    {
        $open = Configure::read('Cerebrate.open');
        $menu = [
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
                            'label' => __('Add individual'),
                            'popup' => 1
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
                            'skipTopMenu' => 1,
                            'popup' => 1
                        ],
                        'delete' => [
                            'url' => '/individuals/delete/{{id}}',
                            'label' => __('Delete individual'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1,
                            'popup' => 1
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
                            'label' => __('Add organisation'),
                            'popup' => 1
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
                            'skipTopMenu' => 1,
                            'popup' => 1
                        ],
                        'delete' => [
                            'url' => '/organisations/delete/{{id}}',
                            'label' => __('Delete organisation'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1,
                            'popup' => 1
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
                            'label' => __('Add encryption key'),
                            'popup' => 1
                        ],
                        'edit' => [
                            'url' => '/encryptionKeys/edit/{{id}}',
                            'label' => __('Edit organisation'),
                            'actions' => ['edit'],
                            'skipTopMenu' => 1,
                            'popup' => 1
                        ]
                    ]
                ]
            ],
            'Trust Circles' => [
                'SharingGroups' => [
                    'label' => __('Sharing Groups'),
                    'url' => '/sharingGroups/index',
                    'children' => [
                        'index' => [
                            'url' => '/sharingGroups/index',
                            'label' => __('List sharing groups')
                        ],
                        'add' => [
                            'url' => '/SharingGroups/add',
                            'label' => __('Add sharing group'),
                            'popup' => 1
                        ],
                        'edit' => [
                            'url' => '/SharingGroups/edit/{{id}}',
                            'label' => __('Edit sharing group'),
                            'actions' => ['edit', 'view'],
                            'skipTopMenu' => 1,
                            'popup' => 1
                        ],
                        'delete' => [
                            'url' => '/SharingGroups/delete/{{id}}',
                            'label' => __('Delete sharing group'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1,
                            'popup' => 1
                        ]
                    ]
                ]
            ],
            'Sync' => [
                'Broods' => [
                    'label' => __('Broods'),
                    'url' => '/broods/index',
                    'children' => [
                        'index' => [
                            'url' => '/broods/index',
                            'label' => __('List broods')
                        ],
                        'add' => [
                            'url' => '/broods/add',
                            'label' => __('Add brood'),
                            'popup' => 1
                        ],
                        'view' => [
                            'url' => '/broods/view/{{id}}',
                            'label' => __('View brood'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1
                        ],
                        'edit' => [
                            'url' => '/broods/edit/{{id}}',
                            'label' => __('Edit brood'),
                            'actions' => ['edit', 'delete', 'view'],
                            'skipTopMenu' => 1,
                            'popup' => 1
                        ],
                        'delete' => [
                            'url' => '/broods/delete/{{id}}',
                            'label' => __('Delete brood'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1,
                            'popup' => 1
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
                            'label' => __('Add role'),
                            'popup' => 1
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
                            'skipTopMenu' => 1,
                            'popup' => 1
                        ],
                        'delete' => [
                            'url' => '/roles/delete/{{id}}',
                            'label' => __('Delete role'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1,
                            'popup' => 1
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
                            'label' => __('Add user'),
                            'popup' => 1
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
                            'skipTopMenu' => 1,
                            'popup' => 1
                        ],
                        'delete' => [
                            'url' => '/users/delete/{{id}}',
                            'label' => __('Delete user'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1,
                            'popup' => 1
                        ]
                    ]
                ],
                'MetaTemplates' => [
                    'label' => __('Meta Field Templates'),
                    'url' => '/metaTemplates/index',
                    'children' => [
                        'index' => [
                            'url' => '/metaTemplates/index',
                            'label' => __('List Meta Templates')
                        ],
                        'view' => [
                            'url' => '/metaTemplates/view/{{id}}',
                            'label' => __('View Meta Template'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1
                        ],
                        'delete' => [
                            'url' => '/metaTemplates/delete/{{id}}',
                            'label' => __('Delete Meta Template'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1,
                            'popup' => 1
                        ],
                        'update' => [
                            'url' => '/metaTemplates/update',
                            'label' => __('Update Meta Templates'),
                            'actions' => ['index', 'view'],
                            'skipTopMenu' => 1,
                            'popup' => 1
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
                        ],
                        'delete' => [
                            'url' => '/roles/delete/{{id}}',
                            'label' => __('Delete Role'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1,
                            'popup' => 1
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
                ],
                'Users' => [
                    __('My Profile'),
                    'children' => [
                        'View My Profile' => [
                            'url' => '/users/view',
                            'label' => __('View My Profile')
                        ],
                        'Edit My Profile' => [
                            'url' => '/users/edit',
                            'label' => __('Edit My Profile'),
                            'actions' => ['delete', 'edit', 'view'],
                            'skipTopMenu' => 1,
                            'popup' => 1
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
        foreach ($menu as $group => $subMenu) {
            foreach ($subMenu as $subMenuElementName => $subMenuElement) {
                if (!empty($subMenuElement['url']) && !$this->checkAccessUrl($subMenuElement['url'], true) === true) {
                    unset($menu[$group][$subMenuElementName]);
                    continue;
                }
                if (!empty($subMenuElement['children'])) {
                    foreach ($subMenuElement['children'] as $menuItem => $menuItemData) {
                        if (!empty($menuItemData['url']) && !$this->checkAccessUrl($menuItemData['url'], true) === true) {
                            unset($menu[$group][$subMenuElementName]['children'][$menuItem]);
                            continue;
                        }
                    }
                    $menu[$group][$subMenuElementName]['children'] = array_values($menu[$group][$subMenuElementName]['children']);
                    if (empty($menu[$group][$subMenuElementName]['children'])) {
                        unset($subMenu[$subMenuElementName]);
                    }
                }
            }
            if (empty($menu[$group])) {
                unset($menu[$group]);
            }
        }
        return $menu;
    }
}
