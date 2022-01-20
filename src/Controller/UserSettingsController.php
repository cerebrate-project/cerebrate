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
        if (empty($currentUser['role']['perm_admin'])) {
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
            $settingsForUser = $this->UserSettings->Users->find()->where([
                'id' => $this->request->getQuery('Users_id')
            ])->first();
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

    public function add($user_id = false)
    {
        $currentUser = $this->ACL->getUser();
        $this->CRUD->add([
            'redirect' => ['action' => 'index', $user_id],
            'beforeSave' => function ($data) use ($currentUser) {
                if (empty($currentUser['role']['perm_admin'])) {
                    $data['user_id'] = $currentUser->id;
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $allUsers = $this->UserSettings->Users->find('list', ['keyField' => 'id', 'valueField' => 'username'])->order(['username' => 'ASC']);
        if (empty($currentUser['role']['perm_admin'])) {
            $allUsers->where(['id' => $currentUser->id]);
            $user_id = $currentUser->id;
        }
        $dropdownData = [
            'user' => $allUsers->all()->toArray(),
        ];
        $this->set(compact('dropdownData'));
        $this->set('user_id', $user_id);
    }

    public function edit($id)
    {
        $entity = $this->UserSettings->find()->where([
            'id' => $id
        ])->first();

        if (!$this->isLoggedUserAllowedToEdit($entity)) {
            throw new NotFoundException(__('Invalid {0}.', 'user setting'));
        }

        $entity = $this->CRUD->edit($id, [
            'redirect' => ['action' => 'index', $entity->user_id]
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $dropdownData = [
            'user' => $this->UserSettings->Users->find('list', [
                'sort' => ['username' => 'asc']
            ]),
        ];
        $this->set(compact('dropdownData'));
        $this->set('user_id', $this->entity->user_id);
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

    public function getSettingByName($settingsName)
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

    public function setSetting($settingsName = false)
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

    public function saveSetting()
    {
        if ($this->request->is('post')) {
            $data = $this->ParamHandler->harvestParams([
                'name',
                'value'
            ]);
            $setting = $this->UserSettings->getSettingByName($this->ACL->getUser(), $data['name']);
            if (is_null($setting)) { // setting not found, create it
                $result = $this->UserSettings->createSetting($this->ACL->getUser(), $data['name'], $data['value']);
            } else {
                $result = $this->UserSettings->editSetting($this->ACL->getUser(), $data['name'], $data['value']);
            }
            $success = !empty($result);
            $message = $success ? __('Setting saved') : __('Could not save setting');
            $this->CRUD->setResponseForController('setSetting', $success, $message, $result);
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
    }

    public function getBookmarks($forSidebar = false)
    {
        $bookmarks = $this->UserSettings->getSettingByName($this->ACL->getUser(), $this->UserSettings->BOOKMARK_SETTING_NAME);
        $bookmarks = json_decode($bookmarks['value'], true);
        $this->set('user_id', $this->ACL->getUser()->id);
        $this->set('bookmarks', $bookmarks);
        $this->set('forSidebar', $forSidebar);
        $this->render('/element/UserSettings/saved-bookmarks');
    }

    public function saveBookmark()
    {
        if (!$this->request->is('get')) {
            $result = $this->UserSettings->saveBookmark($this->ACL->getUser(), $this->request->getData());
            $success = !empty($result);
            $message = $success ? __('Bookmark saved') : __('Could not save bookmark');
            $this->CRUD->setResponseForController('saveBookmark', $success, $message, $result);
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $this->set('user_id', $this->ACL->getUser()->id);
    }

    public function deleteBookmark()
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
        $isAllowed = false;
        if (!empty($currentUser['role']['perm_admin'])) {
            $isAllowed = true;
        } else {
            if (is_numeric($setting)) {
                $setting = $this->UserSettings->find()->where([
                    'id' => $setting
                ])->first();
                if (empty($setting)) {
                    return false;
                }
            }
            $isAllowed = $setting->user_id == $currentUser->id;
        }
        return $isAllowed;
    }
}
