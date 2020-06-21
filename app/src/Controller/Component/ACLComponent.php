<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use App\Model\Entity\User;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;

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
        'Pages' => [
            'display' => ['*']
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
        return $this->__error(403, 'You do not have permission to use this functionality.', $soft);
    }

    private function __error($code, $message, $soft = false)
    {
        if ($soft) {
            return $code;
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
        $functionFinder = '/function[\s\n]+(\S+)[\s\n]*\(/';
        $dir = new Folder(APP . 'Controller');
        $files = $dir->find('.*\.php');
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
            foreach ($functions as $function) {
                if (!isset($this->__aclList[$controller])
                || !in_array($function, array_keys($this->__aclList[$controller]))) {
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
}
