<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\UnauthorizedException;


class UserSettingsController extends AppController
{
    public $quickFilterFields = [['name' => true], ['value' => true]];
    public $filterFields = ['name', 'value', 'Users.id'];
    public $containFields = ['Users'];

    public function index()
    {
        $conditions = [];
        $currentUser = $this->ACL->getUser();
        if (empty($currentUser['role']['perm_community_admin'])) {
            $conditions['user_id'] = $currentUser->id;
        }
        $this->CRUD->index([
            'conditions' => $conditions,
            'contain' => $this->containFields,
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        if (!empty($this->request->getQuery('Users_id'))) {
            $conditions = [
                'id' => $this->request->getQuery('Users_id')
            ];
            if (empty($currentUser['role']['perm_community_admin'])) {
                $conditions['organisation_id'] = $currentUser['organisation_id'];
            }
            $settingsForUser = $this->UserSettings->Users->find()->where($conditions)->first();
            if (empty($settingsForUser)) {
                throw new NotFoundException(__('Invalid {0}.', __('user')));
            }
            $this->set('settingsForUser', $settingsForUser);
        }
    }

    public function view($id)
    {
        if (!$this->isLoggedUserAllowedToEdit($id)) {
            throw new NotFoundException(__('Invalid {0}.', 'user setting'));
        }
        $this->CRUD->view($id, [
            'contain' => ['Users']
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function add($user_id=null)
    {
        $validUsers = [];
        $individual_ids = [];
        $currentUser = $this->ACL->getUser();
        if (!$currentUser['role']['perm_community_admin']) {
            if ($currentUser['role']['perm_org_admin']) {
                $validUsers = $this->Users->find('list')->select(['id', 'username'])->order(['username' => 'asc'])->where(['organisation_id' => $currentUser['organisation']['id']])->all()->toArray();
            } else {
                $validUsers = [$currentUser['id'] => $currentUser['username']];
            }
        } else {
            $validUsers = $this->Users->find('list')->select(['id', 'username'])->order(['username' => 'asc'])->all()->toArray();
        }
        $this->CRUD->add([
            'redirect' => ['action' => 'index', $user_id],
            'beforeSave' => function ($data) use ($currentUser, $validUsers) {
                if (!in_array($data['user_id'], array_keys($validUsers))) {
                    throw new MethodNotAllowedException(__('You cannot edit the given user.'));
                }
                $fakeUser = new \stdClass();
                $fakeUser->id = $data['user_id'];
                $existingSetting = $this->UserSettings->getSettingByName($fakeUser, $data['name']);
                if (!empty($existingSetting)) {
                    throw new MethodNotAllowedException(__('You cannot create a setting that already exists for the given user.'));
                }
                $validationResult = $this->UserSettings->validateUserSetting($data, $currentUser);
                if (!$validationResult !== true) {
                    throw new MethodNotAllowedException(__('You cannot create the given user setting. Reason: {0}', $validationResult));
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $dropdownData = [
            'user' => $validUsers,
        ];
        $this->set(compact('dropdownData'));
        $this->set('user_id', $user_id);
    }

    public function edit($id)
    {
        $entity = $this->UserSettings->find()->where([
            'id' => $id
        ])->first();

        $currentUser = $this->ACL->getUser();
        $validUsers = [];
        $individual_ids = [];
        if (!$currentUser['role']['perm_community_admin']) {
            if ($currentUser['role']['perm_org_admin']) {
                $validUsers = $this->Users->find('list')->select(['id', 'username'])->order(['username' => 'asc'])->where(['organisation_id' => $currentUser['organisation']['id']])->all()->toArray();
            } else {
                $validUsers = [$currentUser['id'] => $currentUser['username']];
            }
        } else {
            $validUsers = $this->Users->find('list')->select(['id', 'username'])->order(['username' => 'asc'])->all()->toArray();
        }
        if (!isset($validUsers[$id])) {
            throw new MethodNotAllowedException(__('You do not have permission to edit this user setting.'));
        }
        $dropdownData = [
            'user' => [$entity->user_id => $validUsers[$entity->user_id]],
        ];

        $entity = $this->CRUD->edit($id, [
            'redirect' => ['action' => 'index', $entity->user_id],
            'beforeSave' => function ($data) use ($validUsers, $entity) {
                if (!in_array($data['user_id'], array_keys($validUsers))) {
                    throw new MethodNotAllowedException(__('You cannot edit the given user.'));
                }
                if ($data['user_id'] != $entity->user_id) {
                    throw new MethodNotAllowedException(__('You cannot assign the setting to a different user.'));
                }
                $validationResult = $this->UserSettings->validateUserSetting($data);
                if ($validationResult !== true) {
                    throw new MethodNotAllowedException(__('Setting value: {0}', $validationResult));
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set(compact('dropdownData'));
        $this->set('user_id', $this->entity->user_id);
        $this->set('is_edit', true);
        $this->render('add');
    }

    public function delete($id)
    {
        if (!$this->isLoggedUserAllowedToEdit($id)) {
            throw new NotFoundException(__('Invalid {0}.', 'user setting'));
        }
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    /**
     * Get a setting by name for the currently logged-in user
     *
     * @param [type] $settingsName
     * @return void
     */
    public function getMySettingByName($settingsName)
    {
        $setting = $this->UserSettings->getSettingByName($this->ACL->getUser(), $settingsName);
        if (is_null($setting)) {
            throw new NotFoundException(__('Invalid {0} for user {1}.', __('User setting'), $this->ACL->getUser()->username));
        }
        $this->CRUD->view($setting->id, [
            'contain' => ['Users']
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->render('view');
    }

    public function setMySetting($settingsName = false)
    {
        if (!$this->request->is('get')) {
            $setting = $this->UserSettings->getSettingByName($this->ACL->getUser(), $settingsName);
            if (is_null($setting)) { // setting not found, create it
                $result = $this->UserSettings->createSetting($this->ACL->getUser(), $settingsName, $this->request->getData()['value']);
            } else {
                $result = $this->UserSettings->editSetting($this->ACL->getUser(), $settingsName, $this->request->getData()['value']);
            }
            $success = !empty($result);
            $message = $success ? __('Setting saved') : __('Could not save setting');
            $this->CRUD->setResponseForController('setSetting', $success, $message, $result);
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $this->set('settingName', $settingsName);
    }

    public function saveSetting($user_id = false)
    {
        $user = $this->getRequestedUserIfAllowed($user_id);
        if ($this->request->is('post')) {
            $data = $this->ParamHandler->harvestParams([
                'name',
                'value'
            ]);
            $setting = $this->UserSettings->getSettingByName($user, $data['name']);
            if (is_null($setting)) { // setting not found, create it
                $result = $this->UserSettings->createSetting($user, $data['name'], $data['value']);
            } else {
                $result = $this->UserSettings->editSetting($user, $data['name'], $data['value']);
            }
            $success = !empty($result);
            $message = $success ? __('Setting saved') : __('Could not save setting');
            $this->CRUD->setResponseForController('saveSetting', $success, $message, $result);
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
    }

    public function getMyBookmarks($forSidebar = false)
    {
        $bookmarks = $this->UserSettings->getSettingByName($this->ACL->getUser(), $this->UserSettings->BOOKMARK_SETTING_NAME);
        $bookmarks = json_decode($bookmarks['value'], true);
        $this->set('user_id', $this->ACL->getUser()->id);
        $this->set('bookmarks', $bookmarks);
        $this->set('forSidebar', $forSidebar);
        $this->render('/element/UserSettings/saved-bookmarks');
    }

    public function saveMyBookmark()
    {
        if (!$this->request->is('get')) {
            $errors = null;
            $result = $this->UserSettings->saveBookmark($this->ACL->getUser(), $this->request->getData(), $errors);
            $success = !empty($result);
            $message = $success ? __('Bookmark saved') : ($errors ?? __('Could not save bookmark'));
            $this->CRUD->setResponseForController('saveBookmark', $success, $message, $result);
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $this->set('user_id', $this->ACL->getUser()->id);
    }

    public function deleteMyBookmark()
    {
        if (!$this->request->is('get')) {
            $result = $this->UserSettings->deleteBookmark($this->ACL->getUser(), $this->request->getData());
            $success = !empty($result);
            $message = $success ? __('Bookmark deleted') : __('Could not delete bookmark');
            $this->CRUD->setResponseForController('deleteBookmark', $success, $message, $result);
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $this->set('user_id', $this->ACL->getUser()->id);
    }

    /**
     * isLoggedUserAllowedToEdit
     *
     * @param int|\App\Model\Entity\UserSetting $setting
     * @return boolean
     */
    private function isLoggedUserAllowedToEdit($setting): bool
    {
        $currentUser = $this->ACL->getUser();
        $setting = $this->UserSettings->find()->where([
            'id' => $setting
        ])->first();
        if (empty($setting)) {
            return false;
        }
        $user = $this->UserSettings->find()->where([
            'id' => $setting->id
        ])->first();

        if ($this->ACL->canEditUser($currentUser, $user)) {
            return true;
        }
        return false;
    }

    /**
     * Return the requested user if user permissions allow it. Otherwise, return the user currently logged-in
     *
     * @param bool|int $user_id
     * @return void
     */
    private function getRequestedUserIfAllowed($user_id = false)
    {
        $currentUser = $this->ACL->getUser();
        if (is_bool($user_id)) {
            return $currentUser;
        }
        if (!empty($currentUser['role']['perm_community_admin'])) {
            $user = $this->Users->get($user_id, [
                'contain' => ['Roles', 'Individuals' => 'Organisations']
            ]);
        } else {
            $user = $currentUser;
        }
        return $user;
    }
}
