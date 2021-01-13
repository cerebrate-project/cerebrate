<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Event\EventInterface;
use Cake\Utility\Text;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Error\Debugger;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/4/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    public $isRest = null;
    public $restResponsePayload = null;
    public $user = null;

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');
        $this->loadComponent('RestResponse');
        $this->loadComponent('Security');
        $this->loadComponent('ParamHandler', [
            'request' => $this->request
        ]);
        $this->loadModel('MetaFields');
        $this->loadModel('MetaTemplates');
        $this->loadComponent('CRUD', [
            'request' => $this->request,
            'table' => $this->{$this->modelClass},
            'MetaFields' => $this->MetaFields,
            'MetaTemplates' => $this->MetaTemplates
        ]);
        $this->loadComponent('Authentication.Authentication');
        $this->loadComponent('ACL', [
            'request' => $this->request,
            'Authentication' => $this->Authentication
        ]);
        if (Configure::read('debug')) {
            Configure::write('DebugKit.panels', ['DebugKit.Packages' => true]);
            Configure::write('DebugKit.forceEnable', true);
        }
        $this->loadComponent('CustomPagination');
        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/4/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    public function beforeFilter(EventInterface $event)
    {
        $this->loadModel('Users');
        $this->Users->checkForNewInstance();
        $this->authApiUser();
        $this->ACL->setPublicInterfaces();
        if (!empty($this->request->getAttribute('identity'))) {
            $user = $this->Users->get($this->request->getAttribute('identity')->getIdentifier(), [
                'contain' => ['Roles', 'Individuals' => 'Organisations']
            ]);
            if (!empty($user['disabled'])) {
                $this->Authentication->logout();
                $this->Flash->error(__('The user account is disabled.'));
                return $this->redirect(['controller' => 'Users', 'action' => 'login']);
            }
            unset($user['password']);
            $this->ACL->setUser($user);
            $this->isAdmin = $user['role']['perm_admin'];
        } else if ($this->ParamHandler->isRest()) {
            throw new MethodNotAllowedException(__('Invalid user credentials.'));
        }
        $this->ACL->checkAccess();
        $this->set('menu', $this->ACL->getMenu());
        $this->set('ajax', $this->request->is('ajax'));
        $this->request->getParam('prefix');
        $this->set('darkMode', !empty(Configure::read('Cerebrate.dark')));
        $this->set('baseurl', empty(Configure::read('baseurl')) ? '' : Configure::read('baseurl'));
    }

    private function authApiUser(): void
    {
        if (!empty($_SERVER['HTTP_AUTHORIZATION']) && strlen($_SERVER['HTTP_AUTHORIZATION'])) {
            $this->loadModel('AuthKeys');
            $authKey = $this->AuthKeys->checkKey($_SERVER['HTTP_AUTHORIZATION']);
            if (!empty($authKey)) {
                $this->loadModel('Users');
                $user = $this->Users->get($authKey['user_id']);
                if (!empty($user)) {
                    $this->Authentication->setIdentity($user);
                }
            }
        }
    }

    public function generateUUID()
    {
        $uuid = Text::uuid();
        return $this->RestResponse->viewData(['uuid' => $uuid], 'json');
    }

    public function queryACL()
    {
        return $this->RestResponse->viewData($this->ACL->findMissingFunctionNames());
    }
}
