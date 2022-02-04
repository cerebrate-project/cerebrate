<?php

namespace App\Controller\Open;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Event\EventInterface;
use Cake\Core\Configure;

class IndividualsController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $open = Configure::read('Cerebrate.open');
        if (!empty($open) && in_array('individuals', $open)) {
            $this->Authentication->allowUnauthenticated(['index']);
        }
    }

    public function index()
    {
        $this->CRUD->index([
            'filters' => ['uuid', 'email', 'first_name', 'last_name', 'position', 'Organisations.id'],
            'quickFilters' => ['uuid', 'email', 'first_name', 'last_name', 'position'],
            'contain' => ['Alignments' => 'Organisations']
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('alignmentScope', 'organisations');
        $this->set('metaGroup', 'Public');
    }
}
