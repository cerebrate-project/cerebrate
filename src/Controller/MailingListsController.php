<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Model\Entity\Individual;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Exception;

class MailingListsController extends AppController
{
    public $filterFields = ['MailingLists.uuid', 'MailingLists.name', 'description', 'releasability'];
    public $quickFilterFields = ['MailingLists.uuid', ['MailingLists.name' => true], ['description' => true], ['releasability' => true]];
    public $containFields = ['Users', 'Individuals', 'MetaFields'];
    public $statisticsFields = ['active'];

    public function index()
    {
        $currentUser = $this->ACL->getUser();
        $this->CRUD->index([
            'contain' => $this->containFields,
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'statisticsFields' => $this->statisticsFields,
            'afterFind' => function ($row) use ($currentUser) {
                if (empty($currentUser['role']['perm_community_admin']) && $row['user_id'] != $currentUser['id']) {
                    if (!$this->MailingLists->isIndividualListed($currentUser['individual_id'], $row)) {
                        $row = false;
                    }
                }
                return $row;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function add()
    {
        $currentUser = $this->ACL->getUser();
        $this->CRUD->add([
            'override' => [
                'user_id' => $currentUser['id']
            ],
            'beforeSave' => function ($data) use ($currentUser) {
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function view($id)
    {
        $currentUser = $this->ACL->getUser();
        $this->CRUD->view($id, [
            'contain' => $this->containFields,
            'afterFind' => function($data) use ($currentUser) {
                if (empty($currentUser['role']['perm_community_admin']) && $data['user_id'] != $currentUser['id']) {
                    if (!$this->MailingLists->isIndividualListed($currentUser['individual_id'], $data)) {
                        $data = [];
                    }
                }
                return $data;
            },
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function edit($id = false)
    {
        $currentUser = $this->ACL->getUser();
        $params = [];
        if (empty($currentUser['role']['perm_community_admin'])) {
            $params['conditions'] = ['user_id' => $currentUser['id']];
        }
        $this->CRUD->edit($id, $params);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->render('add');
    }

    public function delete($id)
    {
        $currentUser = $this->ACL->getUser();
        if (empty($currentUser['role']['perm_community_admin'])) {
            $params['conditions'] = ['user_id' => $currentUser['id']];
        }
        $this->CRUD->delete($id, $params);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function listIndividuals($mailinglist_id)
    {
        $currentUser = $this->ACL->getUser();
        $quickFilter = [
            'uuid',
            ['first_name' => true],
            ['last_name' => true],
        ];
        $quickFilterUI = array_merge($quickFilter, [
            ['Registered emails' => true],
        ]);
        $filters = ['uuid', 'first_name', 'last_name', 'quickFilter'];
        $queryParams = $this->ParamHandler->harvestParams($filters);
        $activeFilters = $queryParams['quickFilter'] ?? [];

        $mailingList = $this->MailingLists->find()
            ->where(['MailingLists.id' => $mailinglist_id])
            ->contain(['MetaFields', 'Individuals'])
            ->first();

        if (is_null($mailingList)) {
            throw new NotFoundException(__('Invalid {0}.', Inflector::singularize($this->MailingLists->getAlias())));
        }
        if (empty($currentUser['role']['perm_community_admin']) && $mailingList['user_id'] != $currentUser['id']) {
            if (!$this->MailingLists->isIndividualListed($currentUser['individual_id'], $mailingList)) {
                throw new NotFoundException(__('Invalid {0}.', Inflector::singularize($this->MailingLists->getAlias())));
            }
        }

        $filteringActive = !empty($queryParams['quickFilter']);
        $matchingMetaFieldParentIDs = [];
        if ($filteringActive) {
            // Collect individuals having a matching meta_field for the requested search value
            foreach ($mailingList->meta_fields as $metaField) {
                if (
                    empty($queryParams['quickFilter']) ||
                    (
                        str_contains($metaField->field, 'email') &&
                        str_contains($metaField->value, $queryParams['quickFilter'])
                    )
                ) {
                    $matchingMetaFieldParentIDs[$metaField->parent_id] = true;
                }
            }
        }
        $matchingMetaFieldParentIDs = array_keys($matchingMetaFieldParentIDs);
        unset($mailingList['individuals']); // reset loaded individuals for the filtering to take effect
        // Perform filtering based on the searched values (supports emails from metafield or individual)
        $mailingList = $this->MailingLists->loadInto($mailingList, [
            'Individuals' => function (Query $q) use ($queryParams, $quickFilter, $filteringActive, $matchingMetaFieldParentIDs) {
                $conditions = [];
                if (!empty($queryParams)) {
                    $conditions = $this->CRUD->genQuickFilterConditions($queryParams, $quickFilter);
                }
                if ($filteringActive && !empty($matchingMetaFieldParentIDs)) {
                    $conditions[] = function (QueryExpression $exp) use ($matchingMetaFieldParentIDs) {
                        return $exp->in('Individuals.id', $matchingMetaFieldParentIDs);
                    };
                }
                if ($filteringActive && !empty($queryParams['quickFilter'])) {
                    $conditions[] = [
                        'MailingListsIndividuals.include_primary_email' => true,
                        'Individuals.email LIKE' => "%{$queryParams['quickFilter']}%"
                    ];
                }
                $q->where([
                    'OR' => $conditions
                ]);
                return $q;
            }
        ]);
        $mailingList->injectRegisteredEmailsIntoIndividuals();
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData($mailingList->individuals, 'json');
        }
        $individuals = $this->CustomPagination->paginate($mailingList->individuals);
        $this->set('mailing_list_id', $mailinglist_id);
        $this->set('quickFilter', $quickFilterUI);
        $this->set('activeFilters', $activeFilters);
        $this->set('quickFilterValue', $queryParams['quickFilter'] ?? '');
        $this->set('individuals', $individuals);
    }

    public function addIndividual($mailinglist_id)
    {
        $currentUser = $this->ACL->getUser();
        $params = [
            'contain' => ['Individuals', 'MetaFields']
        ];
        if (empty($currentUser['role']['perm_community_admin'])) {
            $params['conditions'] = ['user_id' => $currentUser['id']];
        }
        $mailingList = $this->MailingLists->get($mailinglist_id, $params);
        $linkedIndividualsIDs = Hash::extract($mailingList, 'individuals.{n}.id');
        $conditions = [];
        if (!empty($linkedIndividualsIDs)) {
            $conditions = [
                'id NOT IN' => $linkedIndividualsIDs
            ];
        }
        $dropdownData = [
            'individuals' => $this->MailingLists->Individuals->getTarget()->find()
                ->order(['first_name' => 'asc'])
                ->where($conditions)
                ->all()
                ->combine('id', 'full_name')
                ->toArray()
        ];
        if ($this->request->is('post') || $this->request->is('put')) {
            $memberIDs = $this->request->getData()['individuals'];
            $chosen_emails = $this->request->getData()['chosen_emails'];
            if (!empty($chosen_emails)) {
                $chosen_emails = json_decode($chosen_emails, true);
                $chosen_emails = !is_null($chosen_emails) ? $chosen_emails : [];
            } else {
                $chosen_emails = [];
            }
            $members = $this->MailingLists->Individuals->getTarget()->find()->where([
                'id IN' => $memberIDs
            ])->all()->toArray();
            $memberToLink = [];
            foreach ($members as $i => $member) {
                $includePrimary = in_array('primary', $chosen_emails[$member->id]);
                $chosen_emails[$member->id] = array_filter($chosen_emails[$member->id], function($entry) {
                    return $entry != 'primary';
                });
                $members[$i]->_joinData = new Entity(['include_primary_email' => $includePrimary]);
                if (!in_array($member->id, $linkedIndividualsIDs)) { // individual are not already in the list
                    $memberToLink[] = $members[$i];
                }
            }

            // save new individuals
            if (!empty($memberToLink)) {
                $success = (bool)$this->MailingLists->Individuals->link($mailingList, $memberToLink);
                if ($success && !empty($chosen_emails[$member->id])) { // Include any remaining emails from the metaFields
                    $emailsFromMetaFields = $this->MailingLists->MetaFields->find()->where([
                        'id IN' => $chosen_emails[$member->id]
                    ])->all()->toArray();
                    $success = (bool)$this->MailingLists->MetaFields->link($mailingList, $emailsFromMetaFields);
                }
            }

            if ($success) {
                $message = __n('{0} individual added to the mailing list.', '{0} Individuals added to the mailing list.', count($members), count($members));
                $mailingList = $this->MailingLists->get($mailingList->id);
            } else {
                $message = __n('The individual could not be added to the mailing list.', 'The Individuals could not be added to the mailing list.', count($members));
            }
            $this->CRUD->setResponseForController('add_individuals', $success, $message, $mailingList, $mailingList->getErrors());
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $this->set(compact('dropdownData'));
        $this->set('mailinglist_id', $mailinglist_id);
        $this->set('mailingList', $mailingList);
    }

    public function removeIndividual($mailinglist_id, $individual_id=null)
    {
        $currentUser = $this->ACL->getUser();
        $params = [
            'contain' => ['Individuals', 'MetaFields']
        ];
        if (empty($currentUser['role']['perm_community_admin'])) {
            $params['conditions'] = ['user_id' => $currentUser['id']];
        }
        $mailingList = $this->MailingLists->get($mailinglist_id, $params);
        $individual = [];
        if (!is_null($individual_id)) {
            $individual = $this->MailingLists->Individuals->get($individual_id);
        }
        if ($this->request->is('post') || $this->request->is('delete')) {
            $success = false;
            if (!is_null($individual_id)) {
                $individualToRemove = $this->MailingLists->Individuals->get($individual_id);
                $metaFieldsIDsToRemove = Hash::extract($mailingList, 'meta_fields.{n}.id');
                if (!empty($metaFieldsIDsToRemove)) {
                    $metaFieldsToRemove = $this->MailingLists->MetaFields->find()->where([
                        'id IN' => $metaFieldsIDsToRemove,
                        'parent_id' => $individual_id,
                    ])->all()->toArray();
                }
                $success = (bool)$this->MailingLists->Individuals->unlink($mailingList, [$individualToRemove]);
                if ($success && !empty($metaFieldsToRemove)) {
                    $success = (bool)$this->MailingLists->MetaFields->unlink($mailingList, $metaFieldsToRemove);
                }
                if ($success) {
                    $message = __('{0} removed from the mailing list.', $individualToRemove->full_name);
                    $mailingList = $this->MailingLists->get($mailingList->id);
                } else {
                    $message = __n('{0} could not be removed from the mailing list.', $individual->full_name);
                }
                $this->CRUD->setResponseForController('remove_individuals', $success, $message, $mailingList, $mailingList->getErrors());
            } else {
                $params = $this->ParamHandler->harvestParams(['ids']);
                if (!empty($params['ids'])) {
                    $params['ids'] = json_decode($params['ids']);
                }
                if (empty($params['ids'])) {
                    throw new NotFoundException(__('Invalid {0}.', Inflector::singularize($this->MailingLists->Individuals->getAlias())));
                } 
                $individualsToRemove = $this->MailingLists->Individuals->find()->where([
                    'id IN' => array_map('intval', $params['ids'])
                ])->all()->toArray();
                $metaFieldsIDsToRemove = Hash::extract($mailingList, 'meta_fields.{n}.id');
                if (!empty($metaFieldsIDsToRemove)) {
                    $metaFieldsToRemove = $this->MailingLists->MetaFields->find()->where([
                        'id IN' => $metaFieldsIDsToRemove,
                    ])->all()->toArray();
                }
                $unlinkSuccesses = 0;
                foreach ($individualsToRemove as $individualToRemove) {
                    $success = (bool)$this->MailingLists->Individuals->unlink($mailingList, [$individualToRemove]);
                    $results[] = $success;
                    if ($success) {
                        $unlinkSuccesses++;
                    }
                }
                $mailingList = $this->MailingLists->get($mailingList->id);
                $success = $unlinkSuccesses == count($individualsToRemove);
                $message = __(
                    '{0} {1} have been removed.',
                    $unlinkSuccesses == count($individualsToRemove) ? __('All') : sprintf('%s / %s', $unlinkSuccesses, count($individualsToRemove)),
                    Inflector::singularize($this->MailingLists->Individuals->getAlias())
                );
                $this->CRUD->setResponseForController('remove_individuals', $success, $message, $mailingList, []);
            }
            
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $this->set('mailinglist_id', $mailinglist_id);
        $this->set('mailingList', $mailingList);
        if (!empty($individual)) {
            $this->set('deletionText', __('Are you sure you want to remove `{0} ({1})` from the mailing list?', $individual->full_name, $individual->email));
        } else {
            $this->set('deletionText', __('Are you sure you want to remove multiples individuals from the mailing list?'));
        }
        $this->set('postLinkParameters', ['action' => 'removeIndividual', $mailinglist_id, $individual_id]);
        $this->viewBuilder()->setLayout('ajax');
        $this->render('/genericTemplates/delete');
    }
}
