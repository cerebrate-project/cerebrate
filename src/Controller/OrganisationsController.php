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

class OrganisationsController extends AppController
{

    public $quickFilterFields = [['name' => true], 'uuid', 'nationality', 'sector', 'type', 'url'];
    public $filterFields = ['name', 'uuid', 'nationality', 'sector', 'type', 'url', 'Alignments.id', 'MetaFields.field', 'MetaFields.value', 'MetaFields.MetaTemplates.name'];
    public $containFields = ['Alignments' => 'Individuals', 'OrgGroups'];
    public $statisticsFields = ['nationality', 'sector'];

    public function index()
    {
        $customContextFilters = [
            // [
            //     'label' => __('ENISA Accredited'),
            //     'filterCondition' => [
            //         'MetaFields.field' => 'enisa-tistatus',
            //         'MetaFields.value' => 'Accredited',
            //         'MetaFields.MetaTemplates.name' => 'ENISA CSIRT Network'
            //     ]
            // ],
            // [
            //     'label' => __('ENISA not-Accredited'),
            //     'filterCondition' => [
            //         'MetaFields.field' => 'enisa-tistatus',
            //         'MetaFields.value !=' => 'Accredited',
            //         'MetaFields.MetaTemplates.name' => 'ENISA CSIRT Network'
            //     ]
            // ],
            // [
            //     'label' => __('ENISA CSIRT Network (GOV)'),
            //     'filterConditionFunction' => function ($query) {
            //         return $this->CRUD->setParentConditionsForMetaFields($query, [
            //             'ENISA CSIRT Network' => [
            //                 [
            //                     'field' => 'constituency',
            //                     'value LIKE' => '%Government%',
            //                 ],
            //                 [
            //                     'field' => 'csirt-network-status',
            //                     'value' => 'Member',
            //                 ],
            //             ]
            //         ]);
            //     }
            // ],
        ];

        $loggedUserOrganisationNationality = $this->ACL->getUser()['organisation']['nationality'];
        if (!empty($loggedUserOrganisationNationality)) {
            $customContextFilters[] = [
                'label' => __('Country: {0}', $loggedUserOrganisationNationality),
                'filterCondition' => [
                    'nationality' => $loggedUserOrganisationNationality,
                ]
            ];
        }
        $additionalContainFields = [];
        if ($this->ParamHandler->isRest()) {
            $additionalContainFields[] = 'MetaFields';
        }
        $containFields = array_merge($this->containFields, $additionalContainFields);
        $conditions = [];
        $OrgGroups = TableRegistry::getTableLocator()->get('OrgGroups');
        $administeredOrgs = $OrgGroups->getGroupOrgIdsForUser($this->ACL->getUser());
        $administeredOrgs[] = $this->ACL->getUser()['organisation_id'];
        if (!$this->Organisations->canUserSeeOtherOrganisations($this->ACL->getUser())) {
            $conditions['id IN'] = $administeredOrgs;
        }
        $this->set('validOrgs', $this->Users->getValidOrgsForUser($this->ACL->getUser()));
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'quickFilterForMetaField' => ['enabled' => true, 'wildcard_search' => true],
            'contextFilters' => [
                'custom' => $customContextFilters,
            ],
            'contain' => $containFields,
            'conditions' => $conditions,
            'statisticsFields' => $this->statisticsFields,
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('alignmentScope', 'individuals');
        $this->set('metaGroup', 'ContactDB');
    }

    public function filtering()
    {
        $this->CRUD->filtering();
    }

    public function add()
    {
        $this->CRUD->add();
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function view($id)
    {
        $OrgGroups = TableRegistry::getTableLocator()->get('OrgGroups');
        $administeredOrgs = $OrgGroups->getGroupOrgIdsForUser($this->ACL->getUser());
        $isOrgManagedByUser = in_array($id, $administeredOrgs);

        if (!$isOrgManagedByUser && !$this->Organisations->canUserSeeOtherOrganisations($this->ACL->getUser())) {
            throw new NotFoundException(__('Invalid {0}.', 'Organisation'));
        }

        $this->CRUD->view($id, ['contain' => ['Alignments' => 'Individuals', 'OrgGroups']]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('canEdit', $this->canEdit($id));
    }

    public function edit($id)
    {
        $OrgGroups = TableRegistry::getTableLocator()->get('OrgGroups');
        $administeredOrgs = $OrgGroups->getGroupOrgIdsForUser($this->ACL->getUser());
        $isOrgManagedByUser = in_array($id, $administeredOrgs);

        if (!$isOrgManagedByUser && !$this->Organisations->canUserSeeOtherOrganisations($this->ACL->getUser())) {
            throw new NotFoundException(__('Invalid {0}.', 'Organisation'));
        }
        if (!$this->canEdit($id)) {
            throw new MethodNotAllowedException(__('You cannot modify that organisation.'));
        }
        $currentUser = $this->ACL->getUser();
        $this->CRUD->edit($id, [
            'beforeSave' => function($data) use ($currentUser) {
                if (!$currentUser['role']['perm_community_admin']) {
                    unset($data['uuid']);
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
        $this->render('add');
    }

    public function delete($id)
    {
        $OrgGroups = TableRegistry::getTableLocator()->get('OrgGroups');
        $administeredOrgs = $OrgGroups->getGroupOrgIdsForUser($this->ACL->getUser());
        $isOrgManagedByUser = in_array($id, $administeredOrgs);

        if (!$isOrgManagedByUser && !$this->Organisations->canUserSeeOtherOrganisations($this->ACL->getUser())) {
            throw new NotFoundException(__('Invalid {0}.', 'Organisation'));
        }
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }

    public function tag($id)
    {
        $OrgGroups = TableRegistry::getTableLocator()->get('OrgGroups');
        $administeredOrgs = $OrgGroups->getGroupOrgIdsForUser($this->ACL->getUser());
        $isOrgManagedByUser = in_array($id, $administeredOrgs);

        if (!$isOrgManagedByUser && !$this->Organisations->canUserSeeOtherOrganisations($this->ACL->getUser())) {
            throw new NotFoundException(__('Invalid {0}.', 'Organisation'));
        }
        if (!$this->canEdit($id)) {
            throw new MethodNotAllowedException(__('You cannot tag that organisation.'));
        }
        $this->CRUD->tag($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function untag($id)
    {
        $OrgGroups = TableRegistry::getTableLocator()->get('OrgGroups');
        $administeredOrgs = $OrgGroups->getGroupOrgIdsForUser($this->ACL->getUser());
        $isOrgManagedByUser = in_array($id, $administeredOrgs);

        if (!$isOrgManagedByUser && !$this->Organisations->canUserSeeOtherOrganisations($this->ACL->getUser())) {
            throw new NotFoundException(__('Invalid {0}.', 'Organisation'));
        }
        if (!$this->canEdit($id)) {
            throw new MethodNotAllowedException(__('You cannot untag that organisation.'));
        }
        $this->CRUD->untag($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function viewTags($id)
    {
        $OrgGroups = TableRegistry::getTableLocator()->get('OrgGroups');
        $administeredOrgs = $OrgGroups->getGroupOrgIdsForUser($this->ACL->getUser());
        $isOrgManagedByUser = in_array($id, $administeredOrgs);

        if (!$isOrgManagedByUser && !$this->Organisations->canUserSeeOtherOrganisations($this->ACL->getUser())) {
            throw new NotFoundException(__('Invalid {0}.', 'Organisation'));
        }
        $this->CRUD->viewTags($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    private function canEdit($orgId): bool
    {
        $currentUser = $this->ACL->getUser();
        if ($currentUser['role']['perm_community_admin']) {
            return true;
        }

        if ($currentUser['role']['perm_org_admin'] && $currentUser['organisation']['id'] == $orgId) {
            return true;
        }

        if ($currentUser['role']['perm_group_admin'] && in_array($orgId, $this->Users->getValidOrgsForUser($currentUser))) {
            return true;
        }
        return false;
    }
}
