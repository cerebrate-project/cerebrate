<?php
/**
 * Bootstrap Tabs helper
 * Options:
 *  [style]
 *      - fill: Should the navigation occupies all space
 *      - justify: Should the navigation be justified (accept: ['center', 'end'])
 *      - pills: Should the tabs be  pills
 *      - vertical: Should the navigation be placed on the left side of the content
 *      - vertical-size: How many boostrap cols be used for the navigation (only used when `vertical` is true)
 *      - card: Should the navigation be placed in a bootstrap card
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
use Cake\Utility\Security;
use InvalidArgumentException;

class BootstrapHelper extends Helper
{
    public function tabs($options)
    {
        $bsTabs = new BootstrapTabs($options);
        return $bsTabs->tabs();
    }
}

class BootstrapTabs extends Helper
{
    private $defaultOptions = [
        'nav-fill' => false,
        'nav-justify' => false,
        'pills' => false,
        'vertical' => false,
        'vertical-size' => 3,
        'card' => false,
        'nav-class' => [],
        'nav-item-class' => [],
        'content-class' => [],
        'data' => [
            'navs' => [],
            'content' => [],
        ],
    ];

    private $allowedOptionValues = [
        'nav-justify' => [false, 'center', 'end'],
    ];

    private $options = null;
    private $bsClasses = null;

    function __construct($options) {
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
        $this->bsClasses = [
            'nav' => [],
            'nav-item' => $this->options['nav-item-class'],
    
        ];

        if (!empty($this->options['nav-justify'])) {
            $this->bsClasses['nav'][] = 'justify-content-' . $this->options['nav-justify'];
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

        if ($this->options['nav-fill']) {
            $this->bsClasses['nav'][] = 'nav-fill';
        }
        if ($this->options['nav-justify']) {
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

        if (!is_array($this->options['nav-class'])) {
            $this->options['nav-class'] = [$this->options['nav-class']];
        }
        if (!is_array($this->options['content-class'])) {
            $this->options['content-class'] = [$this->options['content-class']];
        }
    }

    private function checkOptionValidity()
    {
        foreach ($this->allowedOptionValues as $option => $values) {
            if (!isset($this->options[$option])) {
                throw new InvalidArgumentException(__('Option `{0}` should have a value', $option));
            }
            if (!in_array($this->options[$option], $values)) {
                throw new InvalidArgumentException(__('Option `{0}` is not a valid option for `{1}`. Accepted values: {2}', json_encode($this->options[$option]), $option, json_encode($values)));
            }
        }
        if (empty($this->data['navs'])) {
            throw new InvalidArgumentException(__('No navigation data provided'));
        }
        if ($this->options['card'] && $this->options['vertical']) {
            throw new InvalidArgumentException(__('`card` option can only be used on horizontal mode'));
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
            $html .= $this->genNode('div', ['class' => ['card']]);
            $html .= $this->genNode('div', ['class' => ['card-header']]);
        }
        $html .= $this->genNav();
        if ($this->options['card']) {
            $html .= '</div>';
            $html .= $this->genNode('div', ['class' => ['card-body']]);
        }
        $html .= $this->genContent();
        if ($this->options['card']) {
            $html .= '</div>';
            $html .= '</div>';
        }
        return $html;
    }

    private function genVerticalTabs()
    {
        $html = $this->genNode('div', ['class' => ['row']]);;
            $html .= $this->genNode('div', ['class' => 'col-' . $this->options['vertical-size']]);
                $html .= $this->genNav();
            $html .= '</div>';
            $html .= $this->genNode('div', ['class' => 'col-' . (12 - $this->options['vertical-size'])]);
                $html .= $this->genContent();
            $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function genNav()
    {
        $html = $this->genNode('ul', [
            'class' => array_merge(['nav'], $this->bsClasses['nav'], $this->options['nav-class']),
            'role' => 'tablist',
        ]);
        foreach ($this->data['navs'] as $navItem) {
            $html .= $this->genNavItem($navItem);
        }
        $html .= '</ul>';
        return $html;
    }

    private function genNavItem($navItem)
    {
        $html = $this->genNode('li', [
            'class' => array_merge(['nav-item'], $this->bsClasses['nav-item'], $this->options['nav-item-class']),
            'role' => 'presentation',
        ]);
        $html .= $this->genNode('a', [
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
        $html .= '</a></li>';
        return $html;
    }

    private function genContent()
    {
        $html = $this->genNode('div', [
            'class' => array_merge(['tab-content'], $this->options['content-class']),
        ]);
        foreach ($this->data['content'] as $i => $content) {
            $navItem = $this->data['navs'][$i];
            $html .= $this->genContentItem($navItem, $content);
        }
        $html .= '</ul>';
        return $html;
    }

    private function genContentItem($navItem, $content)
    {
        $html = $this->genNode('div', [
            'class' => array_merge(['tab-pane', 'fade'], [!empty($navItem['active']) ? 'show active' : '']),
            'role' => 'tabpanel',
            'id' => $navItem['id'],
            'aria-labelledby' => $navItem['id'] . '-tab'
        ]);
        $html .= $content;
        $html .= '</div>';
        return $html;
    }

    private function genNode($node, $params)
    {
        return sprintf('<%s %s>', $node, $this->genHTMLParams($params));
    }

    private function genHTMLParams($params)
    {
        $html = '';
        foreach ($params as $k => $v) {
            $html .= $this->genHTMLParam($k, $v) . ' ';
        }
        return $html;
    }

    private function genHTMLParam($paramName, $values)
    {
        if (!is_array($values)) {
            $values = [$values];
        }
        return sprintf('%s="%s"', $paramName, implode(' ', $values));
    }
}

