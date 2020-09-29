<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;

class InstanceController extends AppController
{
    public function home()
    {
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
        $this->set('md', file_get_contents(ROOT . '/README.md'));
    }

    public function status()
    {
        $data = file_get_contents(APP . 'VERSION.json');
        $data = json_decode($data, true);
        $data['user'] = $this->ACL->getUser();
        return $this->RestResponse->viewData($data, 'json');
    }
}
