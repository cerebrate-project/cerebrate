<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Error\Debugger;

class MailingListsController extends AppController
{
    public $filterFields = ['MailingLists.uuid', 'MailingLists.name', 'description', 'releasability'];
    public $quickFilterFields = ['MailingLists.uuid', ['MailingLists.name' => true], ['description' => true], ['releasability' => true]];
    public $containFields = ['Users', 'Individuals'];

    public function index()
    {
        $this->CRUD->index([
            'contain' => $this->containFields,
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function add()
    {
        $this->CRUD->add([
            'override' => [
                'user_id' => $this->ACL->getUser()['id']
            ]
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function view($id)
    {
        $this->CRUD->view($id, [
            'contain' => $this->containFields
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function edit($id = false)
    {
        $this->CRUD->edit($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function listIndividuals($mailinglist_id)
    {
        $individuals = $this->MailingLists->get($mailinglist_id, [
            'contain' => 'Individuals'
        ])->individuals;
        $params = $this->ParamHandler->harvestParams(['quickFilter']);
        if (!empty($params['quickFilter'])) {
            // foreach ($sharingGroup['sharing_group_orgs'] as $k => $org) {
            //     if (strpos($org['name'], $params['quickFilter']) === false) {
            //         unset($sharingGroup['sharing_group_orgs'][$k]);
            //     }
            // }
            // $sharingGroup['sharing_group_orgs'] = array_values($sharingGroup['sharing_group_orgs']);
        }
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData($individuals, 'json');
        }
        $this->set('mailing_list_id', $mailinglist_id);
        $this->set('individuals', $individuals);
    }

    public function addIndividual($mailinglist_id)
    {
        $mailingList = $this->MailingLists->get($mailinglist_id, [
            'contain' => 'Individuals'
        ]);
        $conditions = [];
        $dropdownData = [
            'individuals' => $this->MailingLists->Individuals->getTarget()->find('list', [
                'sort' => ['name' => 'asc'],
                'conditions' => $conditions
            ])
        ];
        if ($this->request->is('post') || $this->request->is('put')) {
            $memberIDs = $this->request->getData()['individuals'];
            $members = $this->MailingLists->Individuals->getTarget()->find()->where([
                'id IN' => $memberIDs
            ])->all()->toArray();
            $success = (bool)$this->MailingLists->Individuals->link($mailingList, $members);
            if ($success) {
                $message = __n('%s individual added to the mailing list.', '%s Individuals added to the mailing list.', count($members), count($members));
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
        $mailingList = $this->MailingLists->get($mailinglist_id, [
            'contain' => 'Individuals'
        ]);
        $individual = [];
        if (!is_null($individual_id)) {
            $individual = $this->MailingLists->Individuals->get($individual_id);
        }
        if ($this->request->is('post') || $this->request->is('delete')) {
            $success = false;
            if (!is_null($individual_id)) {
                $individual = $this->MailingLists->Individuals->get($individual_id);
                $success = (bool)$this->MailingLists->Individuals->unlink($mailingList, [$individual]);
                if ($success) {
                    $message = __('Individual removed from the mailing list.');
                    $mailingList = $this->MailingLists->get($mailingList->id);
                } else {
                    $message = __n('Individual could not be removed from the mailing list.');
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
                $individuals = $this->MailingLists->Individuals->find()->where([
                    'id IN' => array_map('intval', $params['ids'])
                ])->all()->toArray();
                $unlinkSuccesses = 0;
                foreach ($individuals as $individual) {
                    $success = (bool)$this->MailingLists->Individuals->unlink($mailingList, [$individual]);
                    $results[] = $success;
                    if ($success) {
                        $unlinkSuccesses++;
                    }
                }
                $mailingList = $this->MailingLists->get($mailingList->id);
                $success = $unlinkSuccesses == count($individuals);
                $message = __(
                    '{0} {1} have been removed.',
                    $unlinkSuccesses == count($individuals) ? __('All') : sprintf('%s / %s', $unlinkSuccesses, count($individuals)),
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