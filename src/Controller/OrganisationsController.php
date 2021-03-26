<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;

class OrganisationsController extends AppController
{

    public $filters = ['name', 'uuid', 'nationality', 'sector', 'type', 'url', 'Alignments.id', 'MetaFields.field', 'MetaFields.value', 'MetaFields.MetaTemplates.name'];

    public function index()
    {
        $this->CRUD->index([
            'filters' => $this->filters,
            'quickFilters' => [['name' => true], 'uuid', 'nationality', 'sector', 'type', 'url'],
            'contextFilters' => [
                'custom' => [
                    [
                        'label' => __('ENISA Accredited'),
                        'filterCondition' => [
                            'MetaFields.field' => 'enisa-tistatus',
                            'MetaFields.value' => 'Accredited',
                            'MetaFields.MetaTemplates.name' => 'ENISA CSIRT Network'
                        ]
                    ],
                    [
                        'label' => __('ENISA not-Accredited'),
                        'filterCondition' => [
                            'MetaFields.field' => 'enisa-tistatus',
                            'MetaFields.value !=' => 'Accredited',
                            'MetaFields.MetaTemplates.name' => 'ENISA CSIRT Network'
                        ]
                    ],
                    [
                        'label' => __('ENISA CSIRT Network (GOV)'),
                        'filterConditionFunction' => function($query) {
                            return $this->CRUD->setParentConditionsForMetaFields($query, [
                                'ENISA CSIRT Network' => [
                                    [
                                        'field' => 'constituency',
                                        'value LIKE' => '%Government%',
                                    ],
                                    [
                                        'field' => 'csirt-network-status',
                                        'value' => 'Member',
                                    ],
                                ]
                            ]);
                        }
                    ]
                ],
            ],
            'contain' => ['Alignments' => 'Individuals']
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
        $this->set('metaGroup', 'ContactDB');
    }

    public function view($id)
    {
        $this->CRUD->view($id, ['contain' => ['Alignments' => 'Individuals']]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }

    public function edit($id)
    {
        $this->CRUD->edit($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }
}
