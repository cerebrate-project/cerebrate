<?php
// set a namespace for the module
namespace SkeletonConnector;

// These can be left as is. We want to have access to the commonconnector tools as well as basic http / exception functions
require_once(ROOT . '/src/Lib/default/local_tool_connectors/CommonConnectorTools.php');
use CommonConnectorTools\CommonConnectorTools;
use Cake\Http\Client;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Client\Response;

class SkeletonConnector extends CommonConnectorTools
{

    /*
     *
     * ====================================== Metainformation block ======================================
     *
     */
    public $description = '';
    public $connectorName = 'SkeletonConnector';
    public $name = 'Skeleton';
    public $version = '0.1';

    // exposed function list and configuration
    public $exposedFunctions = [
        'myIndexAction' => [
            'type' => 'index',
            'scope' => 'child',
            'params' => [
                'quickFilter',
                'sort',
                'direction',
                'page',
                'limit'
            ]
        ],
        'myFormAction' => [
            'type' => 'formAction',
            'scope' => 'childAction',
            'params' => [
                'setting',
                'value'
            ],
            'redirect' => 'serverSettingsAction'
        ]
    ];
    public $settings = [
        'url' => [
            'type' => 'text'
        ],
        'authkey' => [
            'type' => 'text'
        ],
        'skip_ssl' => [
            'type' => 'boolean'
        ],
    ];
    public $settingsPlaceholder = [
        'url' => 'https://your.url',
        'authkey' => '',
        'skip_ssl' => '0',
    ];

    public function health(Object $connection): array
    {
        /*
            returns an array with 2 keys:
            [
                status: the numeric response code (0: UNKNOWN, 1: OK, 2: ISSUES, 3: ERROR),
                message: status message shown
            ]
        */
        return $health;
    }

    /*
     *
     * ====================================== Exposed custom functions ======================================
     *
     */

    public function myIndexAction(array $params): array
    {
        // $data = get data from local tool

        //if we want to filter it via the quicksearch
        if (!empty($params['quickFilter'])) {
            // filter $data
        }

        /*
         * return the data embedded in a generic index parameter array
         * 
         * For more information on the structure of the index, refer to the cerebrate/src/templates/element/genericElements/IndexTable/index_table.php factory
         * Valid form field types can be found at cerebrate/src/templates/element/genericElements/IndexTable/Fields
         * Be aware that some form fields require additional parameters, so make sure that you check the available parameters in the form field template file
         * 
         */

        return [
            'type' => 'index',
            'title' => false,
            'description' => false,
            'data' => [
                'data' => $data,
                'skip_pagination' => 1,
                'top_bar' => [
                    'children' => [
                        [
                            'type' => 'search',
                            'button' => __('Search'),
                            'placeholder' => __('Enter value to search'),
                            'data' => '',
                            'searchKey' => 'value',
                            'additionalUrlParams' => $urlParams
                        ]
                    ]
                ],
                'fields' => [
                    [
                        'name' => 'field1_name',
                        'sort' => 'field1.path',
                        'data_path' => 'field1.path',
                    ]
                ],
                'pull' => 'right',
                'actions' => [
                    [
                        'open_modal' => '/localTools/action/' . h($params['connection']['id']) . '/myForm?myKey={{0}}',
                        'modal_params_data_path' => ['myKey'],
                        'icon' => 'font_awesome_icon_name',
                        'reload_url' => '/localTools/action/' . h($params['connection']['id']) . '/myIndex'
                    ]
                ]
            ]
        ];
    }


    public function myFormAction(array $params): array
    {
        if ($params['request']->is(['get'])) {
            /*
             * Usually on get requests we return a form to the user
             * 
             * This form can either simply be a confirmation prompt (such as the example below), or a full fledged form with diverse form fields
             * In the latter case, refer to the cerebrate/src/templates/element/Form/genericForm.php library
             * For individual form field types, see cerebrate/src/templates/element/Form/Fields/*.php
             * 
             */
            return [
                'data' => [
                    'title' => __('My Form Title'),
                    'description' => __('My form description'),
                    'submit' => [
                        'action' => $params['request']->getParam('action')
                    ],
                    'url' => ['controller' => 'localTools', 'action' => 'action', h($params['connection']['id']), 'myFormAction']
                ]
            ];
        } elseif ($params['request']->is(['post'])) {
            /*
             * Handle the posted data here.
             * The response should be in the following format:
             * [
             *   "success": 0|1,
             *   "message": "your_message_to_the_user"
             * ]
             * 
             * The message can be hard coded or derived from the output of your interactions with the local tool
             * 
             */
            if ($success) {
                return ['success' => 1, 'message' => __('Action successful.')];
            } else {
                return ['success' => 0, 'message' => __('Action failed spectacularly.')];
            }
        }
        throw new MethodNotAllowedException(__('Invalid http request type for the given action.'));
    }

    /*
     *
     * ====================================== Inter connection functions ======================================
     *
     */

    public function initiateConnection(array $params): array
    {
        /* 
         * Encode initial connection in local tool
         * 
         * This function is invoked by the REQUESTOR side.
         * In some cases, this function doesn't interact with the local tool yet, but it is an option that can be handy for 2-way exchanges
         * Construct the initial payload to be sent to the inbox of the REQUESTEE's Cerebrate node.
         * 
         */
        return $payload;
    }

    public function acceptConnection(array $params): array
    {
        /* 
         * Encode the actions to be taken if the REQUESTEE accepts the interconnection request
         * 
         * The $params parameter will include the payload generated on the REQUESTOR side via the initiateConnection() function
         * Generate access credentials, configurations, whatever is needed on the REQUESTEE's local tool instance and encode the outcome in the payload
         * Make sure that the payload includes everything needed by finaliseConnection() to interconnect the REQUESTOR's tool to that of REQUESTEE
         * 
         */
        return $payload;
    }

    public function finaliseConnection(array $params): bool
    {
        /* 
         * Encode the actions to be taken once the positive response from the REQUESTEE arrives back at the REQUESTOR's instance
         * 
         * At this point both parties have agreed to the interconnection, REQUESTEE has sent their credentials / access information back to REQUESTOR
         * The REQUESTOR needs to encode the connection based on the instructions generated by acceptConnection() on the REQUESTEE side
         * It is sufficient to return true on success.
         * 
         */
        return $success;
    }
}

 ?>
