<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use App\Model\Entity\User;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Utility\Inflector;
use Cake\Routing\Router;

class APIRearrangeComponent extends Component
{
    public function rearrangeForAPI(object $data): object
    {
        if (is_subclass_of($data, 'Iterator')) {
            $data->each(function ($value, $key) {
                $value->rearrangeForAPI();
            });
        } else {
            $data->rearrangeForAPI();
        }
        return $data;
    }
}