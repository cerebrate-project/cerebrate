<?php
/**
 * Bootstrap Tabs helper
 * Options:
 *  [style]
 *      - fill: Should the navigation items occupy all available space
 *      - justify: Should the navigation items be justified (accept: ['center', 'end'])
 *      - pills: Should the navigation items be pills
 *      - vertical: Should the navigation bar be placed on the left side of the content
 *      - vertical-size: Specify how many boostrap's `cols` should be used for the navigation (only used when `vertical` is true)
 *      - card: Should the navigation be placed in a bootstrap card
 *      - header-variant: The bootstrap variant to be used for the card header
 *      - body-variant: The bootstrap variant to be used for the card body
 *      - nav-class: additional class to add to the nav container
 *      - content-class: additional class to add to the content container
 *  [data]
 *      - data: contains the data for the tabs and content
 *      {
 *          'navs': [{nav-item}, {nav-item}, ...],
 *          'content': [{nav-content}, {nav-content}, ...]
 *      }
 * 
 * # Usage:
 *    echo $this->Bootstrap->Tabs([
 *       'pills' => true,
 *       'card' => true,
 *       'data' => [
 *           'navs' => [
 *               'tab1',
 *               ['text' => 'tab2', 'active' => true],
 *               ['html' => '<b>tab3</b>', 'disabled' => true],
 *           ],
 *           'content' => [
 *               'body1',
 *               '<i>body2</i>',
 *               '~body3~'
 *           ]
 *       ]
 *   ]);
 */

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use InvalidArgumentException;

class BootstrapHelper extends Helper
{
    public function tabs($options)
    {
        $bsTabs = new BootstrapTabs($options);
        return $bsTabs->tabs();
    }

    public function alert($options)
    {
        $bsAlert = new BoostrapAlert($options);
        return $bsAlert->alert();
    }

    public function table($options, $data)
    {
        $bsTable = new BoostrapTable($options, $data);
        return $bsTable->table();
    }

    public function button($options)
    {
        $bsButton = new BoostrapButton($options);
        return $bsButton->button();
    }

    public function badge($options)
    {
        $bsBadge = new BoostrapBadge($options);
        return $bsBadge->badge();
    }

}

class BootstrapGeneric
{
    public static $variants = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark', 'white', 'transparent'];
    protected $allowedOptionValues = [];
    protected $options = [];

    protected function checkOptionValidity()
    {
        foreach ($this->allowedOptionValues as $option => $values) {
            if (!isset($this->options[$option])) {
                throw new InvalidArgumentException(__('Option `{0}` should have a value', $option));
            }
            if (!in_array($this->options[$option], $values)) {
                throw new InvalidArgumentException(__('Option `{0}` is not a valid option for `{1}`. Accepted values: {2}', json_encode($this->options[$option]), $option, json_encode($values)));
            }
        }
    }

    protected static function genNode($node, $params=[], $content="")
    {
        return sprintf('<%s %s>%s</%s>', $node, BootstrapGeneric::genHTMLParams($params), $content, $node);
    }

    protected static function openNode($node, $params=[])
    {
        return sprintf('<%s %s>', $node, BootstrapGeneric::genHTMLParams($params));
    }

    protected static function closeNode($node)
    {
        return sprintf('</%s>', $node);
    }

    protected static function genHTMLParams($params)
    {
        $html = '';
        foreach ($params as $k => $v) {
            $html .= BootstrapGeneric::genHTMLParam($k, $v) . ' ';
        }
        return $html;
    }

    protected static function genHTMLParam($paramName, $values)
    {
        if (!is_array($values)) {
            $values = [$values];
        }
        return sprintf('%s="%s"', $paramName, implode(' ', $values));
    }

    protected static function genericCloseButton($dismissTarget)
    {
        return BootstrapGeneric::genNode('button', [
            'type' => 'button',
            'class' => 'close',
            'data-dismiss' => $dismissTarget,
            'arial-label' => __('Close')
        ], BootstrapGeneric::genNode('span', [
            'arial-hidden' => 'true'
        ], '&times;'));
    }
}

class BootstrapTabs extends BootstrapGeneric
{
    private $defaultOptions = [
        'fill' => false,
        'justify' => false,
        'pills' => false,
        'vertical' => false,
        'vertical-size' => 3,
        'card' => false,
        'header-variant' => 'light',
        'body-variant' => '',
        'nav-class' => [],
        'nav-item-class' => [],
        'content-class' => [],
        'data' => [
            'navs' => [],
            'content' => [],
        ],
    ];
    private $bsClasses = null;

    function __construct($options) {
        $this->allowedOptionValues = [
            'justify' => [false, 'center', 'end'],
            'body-variant' => array_merge(BootstrapGeneric::$variants, ['']),
            'header-variant' => BootstrapGeneric::$variants,
        ];
        $this->processOptions($options);
    }

    public function tabs()
    {
        return $this->genTabs();
    }

    private function processOptions($options)
    {
        $this->options = array_merge($this->defaultOptions, $options);
        $this->data = $this->options['data'];
        $this->checkOptionValidity();
        if (empty($this->data['navs'])) {
            throw new InvalidArgumentException(__('No navigation data provided'));
        }
        $this->bsClasses = [
            'nav' => [],
            'nav-item' => $this->options['nav-item-class'],
    
        ];

        if (!empty($this->options['justify'])) {
            $this->bsClasses['nav'][] = 'justify-content-' . $this->options['justify'];
        }

        if ($this->options['vertical'] && !isset($options['pills']) && !isset($options['card'])) {
            $this->options['pills'] = true;
            $this->options['card'] = true;
        }
    
        if ($this->options['pills']) {
            $this->bsClasses['nav'][] = 'nav-pills';
            if ($this->options['vertical']) {
                $this->bsClasses['nav'][] = 'flex-column';
            }
            if ($this->options['card']) {
                $this->bsClasses['nav'][] = 'card-header-pills';
            }
        } else {
            $this->bsClasses['nav'][] = 'nav-tabs';
            if ($this->options['card']) {
                $this->bsClasses['nav'][] = 'card-header-tabs';
            }
        }

        if ($this->options['fill']) {
            $this->bsClasses['nav'][] = 'nav-fill';
        }
        if ($this->options['justify']) {
            $this->bsClasses['nav'][] = 'nav-justify';
        }

        $activeTab = 0;
        foreach ($this->data['navs'] as $i => $nav) {
            if (!is_array($nav)) {
                $this->data['navs'][$i] = ['text' => $nav];
            }
            if (!isset($this->data['navs'][$i]['id'])) {
                $this->data['navs'][$i]['id'] = 't-' . Security::randomString(8);
            }
            if (!empty($nav['active'])) {
                $activeTab = $i;
            }
        }
        $this->data['navs'][$activeTab]['active'] = true;

        $this->options['vertical-size'] = $this->options['vertical-size'] < 0 || $this->options['vertical-size'] > 11 ? 3 : $this->options['vertical-size'];

        $this->options['header-text-variant'] = $this->options['header-variant'] == 'light' ? 'body' : 'white';
        $this->options['header-border-variant'] = $this->options['header-variant'] == 'light' ? '' : $this->options['header-variant'];
        $this->options['body-text-variant'] = $this->options['body-variant'] == '' ? 'body' : 'white';

        if (!is_array($this->options['nav-class'])) {
            $this->options['nav-class'] = [$this->options['nav-class']];
        }
        if (!is_array($this->options['content-class'])) {
            $this->options['content-class'] = [$this->options['content-class']];
        }
    }

    private function genTabs()
    {
        $html = '';
        if ($this->options['vertical']) {
            $html .= $this->genVerticalTabs();
        } else {
            $html .= $this->genHorizontalTabs();
        }
        return $html;
    }

    private function genHorizontalTabs()
    {
        $html = '';
        if ($this->options['card']) {
            $html .= $this->openNode('div', ['class' => array_merge(['card'], ["border-{$this->options['header-border-variant']}"])]);
            $html .= $this->openNode('div', ['class' => array_merge(['card-header'], ["bg-{$this->options['header-variant']}", "text-{$this->options['header-text-variant']}"])]);
        }
        $html .= $this->genNav();
        if ($this->options['card']) {
            $html .= $this->closeNode('div');
            $html .= $this->openNode('div', ['class' => array_merge(['card-body'], ["bg-{$this->options['body-variant']}", "text-{$this->options['body-text-variant']}"])]);
        }
        $html .= $this->genContent();
        if ($this->options['card']) {
            $html .= $this->closeNode('div');
            $html .= $this->closeNode('div');
        }
        return $html;
    }

    private function genVerticalTabs()
    {
        $html = $this->openNode('div', ['class' => array_merge(['row', ($this->options['card'] ? 'card flex-row' : '')], ["border-{$this->options['header-border-variant']}"])]);
            $html .= $this->openNode('div', ['class' => array_merge(['col-' . $this->options['vertical-size'], ($this->options['card'] ? 'card-header border-right' : '')], ["bg-{$this->options['header-variant']}", "text-{$this->options['header-text-variant']}", "border-{$this->options['header-border-variant']}"])]);
                $html .= $this->genNav();
            $html .= $this->closeNode('div');
            $html .= $this->openNode('div', ['class' => array_merge(['col-' . (12 - $this->options['vertical-size']), ($this->options['card'] ? 'card-body2' : '')], ["bg-{$this->options['body-variant']}", "text-{$this->options['body-text-variant']}"])]);
                $html .= $this->genContent();
            $html .= $this->closeNode('div');
        $html .= $this->closeNode('div');
        return $html;
    }

    private function genNav()
    {
        $html = $this->openNode('ul', [
            'class' => array_merge(['nav'], $this->bsClasses['nav'], $this->options['nav-class']),
            'role' => 'tablist',
        ]);
        foreach ($this->data['navs'] as $navItem) {
            $html .= $this->genNavItem($navItem);
        }
        $html .= $this->closeNode('ul');
        return $html;
    }

    private function genNavItem($navItem)
    {
        $html = $this->openNode('li', [
            'class' => array_merge(['nav-item'], $this->bsClasses['nav-item'], $this->options['nav-item-class']),
            'role' => 'presentation',
        ]);
        $html .= $this->openNode('a', [
            'class' => array_merge(
                ['nav-link'],
                [!empty($navItem['active']) ? 'active' : ''],
                [!empty($navItem['disabled']) ? 'disabled' : '']
            ),
            'data-toggle' => $this->options['pills'] ? 'pill' : 'tab',
            'id' => $navItem['id'] . '-tab',
            'href' => '#' . $navItem['id'],
            'aria-controls' => $navItem['id'],
            'aria-selected' => !empty($navItem['active']),
            'role' => 'tab',
        ]);
        if (!empty($navItem['html'])) {
            $html .= $navItem['html'];
        } else {
            $html .= h($navItem['text']);
        }
        $html .= $this->closeNode('a');
        $html .= $this->closeNode('li');
        return $html;
    }

    private function genContent()
    {
        $html = $this->openNode('div', [
            'class' => array_merge(['tab-content'], $this->options['content-class']),
        ]);
        foreach ($this->data['content'] as $i => $content) {
            $navItem = $this->data['navs'][$i];
            $html .= $this->genContentItem($navItem, $content);
        }
        $html .= $this->closeNode('div');
        return $html;
    }

    private function genContentItem($navItem, $content)
    {
        return $this->genNode('div', [
            'class' => array_merge(['tab-pane', 'fade'], [!empty($navItem['active']) ? 'show active' : '']),
            'role' => 'tabpanel',
            'id' => $navItem['id'],
            'aria-labelledby' => $navItem['id'] . '-tab'
        ], $content);
    }
}

class BoostrapAlert extends BootstrapGeneric {
    private $defaultOptions = [
        'text' => '',
        'html' => null,
        'dismissible' => true,
        'variant' => 'primary',
        'fade' => true
    ];

    private $bsClasses = null;

    function __construct($options) {
        $this->allowedOptionValues = [
            'variant' => BootstrapGeneric::$variants,
        ];
        $this->processOptions($options);
    }

    private function processOptions($options)
    {
        $this->options = array_merge($this->defaultOptions, $options);
        $this->checkOptionValidity();
    }

    public function alert()
    {
        return $this->genAlert();
    }

    private function genAlert()
    {
        $html = $this->openNode('div', [
            'class' => [
                'alert',
                "alert-{$this->options['variant']}",
                $this->options['dismissible'] ? 'alert-dismissible' : '',
                $this->options['fade'] ? 'fade show' : '',
            ],
            'role' => "alert"
        ]);

        $html .= $this->genContent();
        $html .= $this->genCloseButton();
        $html .= $this->closeNode('div');
        return $html;
    }

    private function genCloseButton()
    {
        $html = '';
        if ($this->options['dismissible']) {
            $html .= $this->genericCloseButton('alert');
        }
        return $html;
    }

    private function genContent()
    {
        return !is_null($this->options['html']) ? $this->options['html'] : $this->options['text'];
    }
}

class BoostrapTable extends BootstrapGeneric {
    private $defaultOptions = [
        'striped' => true,
        'bordered' => true,
        'borderless' => false,
        'hover' => true,
        'small' => false,
        'variant' => '',
        'tableClass' => [],
        'headerClass' => [],
        'bodyClass' => [],
    ];

    private $bsClasses = null;

    function __construct($options, $data) {
        $this->allowedOptionValues = [
            'variant' => array_merge(BootstrapGeneric::$variants, [''])
        ];
        $this->processOptions($options);
        $this->fields = $data['fields'];
        $this->items = $data['items'];
        $this->caption = !empty($data['caption']) ? $data['caption'] : '';
    }

    private function processOptions($options)
    {
        $this->options = array_merge($this->defaultOptions, $options);
        $this->checkOptionValidity();
    }

    public function table()
    {
        return $this->genTable();
    }

    private function genTable()
    {
        $html = $this->openNode('table', [
            'class' => [
                'table',
                "table-{$this->options['variant']}",
                $this->options['striped'] ? 'table-striped' : '',
                $this->options['bordered'] ? 'table-bordered' : '',
                $this->options['borderless'] ? 'table-borderless' : '',
                $this->options['hover'] ? 'table-hover' : '',
                $this->options['small'] ? 'table-sm' : '',
                !empty($this->options['variant']) ? "table-{$this->options['variant']}" : '',
                !empty($this->options['tableClass']) ? (is_array($this->options['tableClass']) ? implode(' ', $this->options['tableClass']) : $this->options['tableClass']) : ''
            ],
        ]);

        $html .= $this->genCaption();
        $html .= $this->genHeader();
        $html .= $this->genBody();
        
        $html .= $this->closeNode('table');
        return $html;
    }

    private function genHeader()
    {
        $head =  $this->openNode('thead', [
            'class' => [
                !empty($this->options['headerClass']) ? $this->options['headerClass'] : ''
            ],
        ]);
        $head .= $this->openNode('tr');
        foreach ($this->fields as $i => $field) {
            if (is_array($field)) {
                if (!empty($field['labelHtml'])) {
                    $label = $field['labelHtml'];
                } else {
                    $label = !empty($field['label']) ? $field['label'] : Inflector::humanize($field['key']);
                    $label = h($label);
                }
            } else {
                $label = Inflector::humanize($field);
                $label = h($label);
            }
            $head .= $this->genNode('th', [], $label);
        }
        $head .= $this->closeNode('tr');
        $head .= $this->closeNode('thead');
        return $head;
    }

    private function genBody()
    {
        $body =  $this->openNode('tbody', [
            'class' => [
                !empty($this->options['bodyClass']) ? (is_array($this->options['bodyClass']) ? implode(' ', $this->options['bodyClass']) : $this->options['bodyClass']) : ''
            ],
        ]);
        foreach ($this->items as $i => $row) {
            $body .= $this->genRow($row);
        }
        $body .= $this->closeNode('tbody');
        return $body;
    }

    private function genRow($row)
    {
        $html = $this->openNode('tr',[
            'class' => [
                !empty($row['_rowVariant']) ? "table-{$row['_rowVariant']}" : ''
            ]
        ]);
        if (array_keys($row) !== range(0, count($row) - 1)) { // associative array
            foreach ($this->fields as $i => $field) {
                if (is_array($field)) {
                    $key = $field['key'];
                } else {
                    $key = $field;
                }
                $cellValue = $row[$key];
                $html .= $this->genCell($cellValue, $field, $row);
            }
        } else { // indexed array
            foreach ($row as $cellValue) {
                $html .= $this->genCell($cellValue, $field, $row);
            }
        }
        $html .= $this->closeNode('tr');
        return $html;
    }

    private function genCell($value, $field=[], $row=[])
    {
        if (isset($field['formatter'])) {
            $cellContent = $field['formatter']($value, $row);
        } else {
            $cellContent = h($value);
        }
        return $this->genNode('td', [
            'class' => [
                !empty($row['_cellVariant']) ? "bg-{$row['_cellVariant']}" : ''
            ]
        ], $cellContent);
    }

    private function genCaption()
    {
        return $this->genNode('caption', [], h($this->caption));
    }
}

class BoostrapButton extends BootstrapGeneric {
    private $defaultOptions = [
        'id' => '',
        'text' => '',
        'html' => null,
        'variant' => 'primary',
        'outline' => false,
        'size' => '',
        'block' => false,
        'icon' => null,
        'class' => [],
        'type' => 'button',
        'nodeType' => 'button',
        'params' => []
    ];

    private $bsClasses = [];

    function __construct($options) {
        $this->allowedOptionValues = [
            'variant' => BootstrapGeneric::$variants,
            'size' => ['', 'sm', 'lg'],
            'type' => ['button', 'submit', 'reset']
        ];
        if (empty($options['class'])) {
            $options['class'] = '';
        }
        $options['class'] = !is_array($options['class']) ? [$options['class']] : $options['class'];
        $this->processOptions($options);
    }

    private function processOptions($options)
    {
        $this->options = array_merge($this->defaultOptions, $options);
        $this->checkOptionValidity();

        $this->bsClasses[] = 'btn';
        if ($this->options['outline']) {
            $this->bsClasses[] = "btn-outline-{$this->options['variant']}";
        } else {
            $this->bsClasses[] = "btn-{$this->options['variant']}";
        }
        if (!empty($this->options['size'])) {
            $this->bsClasses[] = "btn-$this->options['size']";
        }
        if ($this->options['block']) {
            $this->bsClasses[] = 'btn-block';
        }
    }

    public function button()
    {
        return $this->genButton();
    }

    private function genButton()
    {
        $html = $this->openNode($this->options['nodeType'], array_merge($this->options['params'], [
            'class' => array_merge($this->options['class'], $this->bsClasses),
            'role' => "alert",
            'type' => $this->options['type']
        ]));

        $html .= $this->genIcon();
        $html .= $this->genContent();
        $html .= $this->closeNode($this->options['nodeType']);
        return $html;
    }

    private function genIcon()
    {
        return $this->genNode('span', [
            'class' => ['mr-1', "fa fa-{$this->options['icon']}"],
        ]);
    }

    private function genContent()
    {
        return !is_null($this->options['html']) ? $this->options['html'] : $this->options['text'];
    }
}

class BoostrapBadge extends BootstrapGeneric {
    private $defaultOptions = [
        'text' => '',
        'variant' => 'primary',
        'pill' => false,
        'title' => ''
    ];

    function __construct($options) {
        $this->allowedOptionValues = [
            'variant' => BootstrapGeneric::$variants,
        ];
        $this->processOptions($options);
    }

    private function processOptions($options)
    {
        $this->options = array_merge($this->defaultOptions, $options);
        $this->checkOptionValidity();
    }

    public function badge()
    {
        return $this->genBadge();
    }

    private function genBadge()
    {
        $html = $this->genNode('span', [
            'class' => [
                'badge',
                "badge-{$this->options['variant']}",
                $this->options['pill'] ? 'badge-pill' : '',
            ],
            'title' => $this->options['title']
        ], h($this->options['text']));
        return $html;
    }
}
