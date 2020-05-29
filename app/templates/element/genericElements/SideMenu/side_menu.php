<?php

$menu = [
    'ContactDB' => [
        'Individuals' => [
            'label' => __('Individuals'),
            'url' => '/individuals/index',
            'children' => [
                'index' => [
                    'url' => '/individuals/index',
                    'label' => __('List individuals')
                ],
                'add' => [
                    'url' => '/individuals/add',
                    'label' => __('Add individual')
                ],
                'view' => [
                    'url' => '/individuals/view/{{id}}',
                    'label' => __('View individual'),
                    'actions' => ['delete', 'edit', 'view']
                ],
                'edit' => [
                    'url' => '/individuals/edit/{{id}}',
                    'label' => __('Edit individual'),
                    'actions' => ['edit', 'delete', 'view']
                ],
                'delete' => [
                    'url' => '/individuals/delete/{{id}}',
                    'label' => __('Delete individual'),
                    'actions' => ['delete', 'edit', 'view']
                ]
            ]
        ],
        'Organisations' => [
            'label' => __('Organisations'),
            'url' => '/organisations/index',
            'children' => [
                'index' => [
                    'url' => '/organisations/index',
                    'label' => __('List organisations')
                ],
                'add' => [
                    'url' => '/organisations/add',
                    'label' => __('Add organisation')
                ],
                'view' => [
                    'url' => '/organisations/view/{{id}}',
                    'label' => __('View organisation'),
                    'actions' => ['delete', 'edit', 'view']
                ],
                'edit' => [
                    'url' => '/organisations/edit/{{id}}',
                    'label' => __('Edit organisation'),
                    'actions' => ['edit', 'delete', 'view']
                ],
                'delete' => [
                    'url' => '/organisations/delete/{{id}}',
                    'label' => __('Delete organisation'),
                    'actions' => ['delete', 'edit', 'view']
                ]
            ]
        ]
    ]
];
$children = '';
if (isset($menu[$metaGroup])) {
    foreach ($menu[$metaGroup] as $scope => $scopeData) {
        $children .= sprintf(
            '<li class="sidebar-header"><a href="%s" class="%s">%s</a></li>',
            empty($scopeData['url']) ? '#' : h($scopeData['url']),
            empty($scopeData['class']) ? '' : h($scopeData['class']),
            empty($scopeData['label']) ? h($scope) : $scopeData['label']
        );
        foreach ($scopeData['children'] as $action => $data) {
            if (
                (!empty($data['requirements']) && !$data['requirements']) ||
                (
                    !empty($data['actions']) &&
                    !in_array($this->request->getParam('action'), $data['actions'])
                ) ||
                !empty($data['actions']) && $scope !== $this->request->getParam('controller')
            ) {
                continue;
            }
            $matches = [];
            preg_match_all('/\{\{.*?\}\}/', $data['url'], $matches);
            if (!empty($matches[0])) {
                $mainEntity = \Cake\Utility\Inflector::underscore(\Cake\Utility\Inflector::singularize($scope));
                foreach ($matches as $match) {
                    $data['url'] = str_replace($match[0], ${$mainEntity}[substr($match[0], 2, 2)], $data['url']);
                }
            }
            $children .= sprintf(
                '<li class="sidebar-element %s"><a href="%s" class="%s">%s</a></li>',
                ($scope === $this->request->getParam('controller') && $action === $this->request->getParam('action')) ? 'active' : '',
                empty($data['url']) ? '#' : h($data['url']),
                empty($data['class']) ? '' : h($data['class']),
                empty($data['label']) ? h($action) : $data['label']
            );
        }
    }
}
echo sprintf(
    '<div class="side-menu-div" id="side-menu-div"><ul class="side-bar-ul" style="width:100%%;">%s</ul></div>',
    $children
);
