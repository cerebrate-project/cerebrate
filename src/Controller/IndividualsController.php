<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;

class IndividualsController extends AppController
{
    public $quickFilterFields = ['uuid', ['email' => true], ['first_name' => true], ['last_name' => true], 'position'];
    public $filterFields = [
        'uuid',
        'email',
        'first_name',
        'last_name',
        'position',
        'Alignments.type',
        ['name' => 'Organisations.id', 'multiple' => true, 'options' => 'getAllOrganisations', 'select2' => true],
    ];
    public $containFields = ['Alignments' => 'Organisations'];
    public $statisticsFields = ['position'];

    public function index()
    {
        $currentUser = $this->ACL->getUser();
        $orgAdmin = !$currentUser['role']['perm_community_admin'] && $currentUser['role']['perm_org_admin'];
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'quickFilterForMetaField' => ['enabled' => true, 'wildcard_search' => true],
            'contain' => $this->containFields,
            'statisticsFields' => $this->statisticsFields,
            'afterFind' => function($data) use ($currentUser) {
                if ($currentUser['role']['perm_community_admin']) {
                    $data['user'] = $this->Individuals->Users->find()->select(['id', 'username', 'Organisations.id', 'Organisations.name'])->contain('Organisations')->where(['individual_id' => $data['id']])->all()->toArray();
                } else if ($currentUser['role']['perm_group_admin']) {
                    $OrgGroups = TableRegistry::getTableLocator()->get('OrgGroups');
                    $orgGroupIds = $OrgGroups->getGroupOrgIdsForUser($currentUser);
                    $data['user'] = $this->Individuals->Users->find()->select(['id', 'username', 'Organisations.id', 'Organisations.name'])->contain('Organisations')->where(['individual_id' => $data['id'], 'Organisations.id IN' => $orgGroupIds])->all()->toArray();
                } else if ($currentUser['role']['perm_org_admin']) {
                    $data['user'] = $this->Individuals->Users->find()->select(['id', 'username', 'Organisations.id', 'Organisations.name'])->contain('Organisations')->where(['individual_id' => $data['id'], 'Organisations.id' => $currentUser['organisation_id']])->all()->toArray();
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $editableIds = [];
        if ($orgAdmin) {
            $editableIds = $this->Individuals->getValidIndividualsToEdit($currentUser);
        }
        $this->set('editableIds', $editableIds);
        $this->set('alignmentScope', 'individuals');
    }

    public function filtering()
    {
        $this->CRUD->filtering();
    }

    public function add()
    {
        $currentUser = $this->ACL->getUser();
        $params = [
            'afterSave' => function($data) use ($currentUser) {
                if (empty($currentUser['role']['perm_community_admin'])) {
                    $this->Individuals->Alignments->setAlignment($currentUser['organisation_id'], $data->id, 'Member');
                }
                return $data;
            }
        ];
        $this->CRUD->add($params);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function view($id)
    {
        $canEdit = $this->canEdit($id);
        $this->CRUD->view($id, [
            'contain' => ['Alignments' => 'Organisations', 'Users' => ['fields' => ['id', 'username']]],
            'afterFind' => function($data) use ($canEdit) {
                if (!empty($data['user'])) {
                    $data['has_user'] = true;
                    if (!$canEdit) {
                        unset($data['user']);
                    }
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('canEdit', $this->canEdit($id));
        $this->set('canDelete', $this->canDelete($id));
    }

    public function edit($id)
    {
        if (!$this->canEdit($id)) {
            throw new MethodNotAllowedException(__('You cannot modify that individual.'));
        }
        $currentUser = $this->ACL->getUser();
        $this->CRUD->edit($id, [
            'beforeSave' => function($data) use ($currentUser) {
                if (!$currentUser['role']['perm_community_admin'] && isset($data['uuid'])) {
                    unset($data['uuid']);
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('canEdit', $this->canEdit($id));
        $this->set('canDelete', $this->canDelete($id));
        $this->render('add');
    }

    public function delete($id)
    {
        $params = [
            'contain' => ['Users'],
            'afterFind' => function($data, $params) {
                if (!empty($data['user'])) {
                    throw new ForbiddenException(__('Individual associated to a user cannot be deleted.'));
                }
                return $data;
            }
        ];
        $this->CRUD->delete($id, $params);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function tag($id)
    {
        if (!$this->canEdit($id)) {
            throw new MethodNotAllowedException(__('You cannot tag that individual.'));
        }
        $this->CRUD->tag($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function untag($id)
    {
        if (!$this->canEdit($id)) {
            throw new MethodNotAllowedException(__('You cannot untag that individual.'));
        }
        $this->CRUD->untag($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function viewTags($id)
    {
        $this->CRUD->viewTags($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    private function canEdit($indId): bool
    {
        $currentUser = $this->ACL->getUser();
        if ($currentUser['role']['perm_community_admin']) {
            return true;
        }
        $validIndividuals = $this->Individuals->getValidIndividualsToEdit($currentUser);
        if (in_array($indId, $validIndividuals)) {
            return true;
        }
        return false;
    }

    private function canDelete($indId): bool
    {
        $associatedUsersCount = $this->Individuals->Users->find()->select(['id'])->where(['individual_id' => $indId])->count();
        if ($associatedUsersCount > 0) {
            return false;
        }
        $currentUser = $this->ACL->getUser();
        if ($currentUser['role']['perm_community_admin']) {
            return true;
        }
        return false;
    }
}
