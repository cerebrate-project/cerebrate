<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;

class RolesController extends AppController
{
    public $filterFields = ['name', 'uuid', 'perm_admin', 'perm_community_admin', 'Users.id', 'perm_org_admin'];
    public $quickFilterFields = ['name'];
    public $containFields = [];

    public function index()
    {
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', $this->isCommunityAdmin ? 'Administration' : 'Cerebrate');
    }

    public function add()
    {
        $rolesModel = $this->Roles;
        $this->CRUD->add([
            'afterSave' => function ($data) use ($rolesModel) {
                if ($data['is_default']) {
                    $rolesModel->query()->update()->set(['is_default' => false])->where(['id !=' => $data->id])->execute();
                }
                return true;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', $this->isCommunityAdmin ? 'Administration' : 'Cerebrate');
    }

    public function view($id)
    {
        $this->CRUD->view($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', $this->isCommunityAdmin ? 'Administration' : 'Cerebrate');
    }

    public function edit($id)
    {
        $rolesModel = $this->Roles;
        $this->CRUD->edit($id, [
            'afterSave' => function ($data) use ($rolesModel) {
                if ($data['is_default']) {
                    $rolesModel->query()->update()->set(['is_default' => false])->where(['id !=' => $data->id])->execute();
                }
                return true;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', $this->isCommunityAdmin ? 'Administration' : 'Cerebrate');
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id, [
            'beforeSave' => function ($data) {
                $userCount = $this->Roles->Users->find()->where(['role_id' => $data['id']])->count();
                if ($userCount > 0) {
                    throw new ForbiddenException(__('You cannot delete a role that has users assigned to it.'));
                }
                return true;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', $this->isCommunityAdmin ? 'Administration' : 'Cerebrate');
    }
}
