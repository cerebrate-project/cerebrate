<?php

namespace App\Controller\Open;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Event\EventInterface;

class OrganisationsController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['index']);
    }

    public function index()
    {
        $this->CRUD->index([
            'filters' => ['name', 'uuid', 'nationality', 'sector', 'type', 'url', 'Alignments.id'],
            'quickFilters' => ['name', 'uuid', 'nationality', 'sector', 'type', 'url'],
            'contain' => ['Alignments' => 'Individuals']
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('alignmentScope', 'individuals');
        $this->set('metaGroup', 'Public');
    }
}
