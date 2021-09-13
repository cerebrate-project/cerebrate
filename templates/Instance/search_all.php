<?php
    $sections = [];
    foreach ($data as $tableName => $tableResult) {
        if (empty($tableResult['amount'])) {
            continue;
        }
        $section = '';
        $table = Cake\ORM\TableRegistry::get($tableName);
        $fieldPath = !empty($table->getDisplayField()) ? $table->getDisplayField() : 'id';
        $section .= sprintf('<span class="d-flex text-nowrap px-2 search-container-model">
            <span class="text-uppercase text-muted mr-3 model-text">%s</span>
            <span class="d-flex align-items-center search-container-divider">
                <hr class="m-0"/>
            </span>
            <span class="font-weight-light text-muted ml-3 model-text">%s</span>
        </span>', h($tableName), $tableResult['amount']);

        foreach ($tableResult['entries'] as $entry) {
            $section .= sprintf('<a class="dropdown-item" href="%s">%s</a>',
                Cake\Routing\Router::URL([
                    'controller' => Cake\Utility\Inflector::pluralize($entry->getSource()),
                    'action' => 'view',
                    h($entry['id'])
                ]),
                h($entry[$fieldPath])
            );
        }
        $remaining = $tableResult['amount'] - count($tableResult['entries']);
        if ($remaining > 0) {
            $section .= sprintf('<a href="%s" class="dropdown-item total-found d-block pr-2">%s <strong class="total-found-number text-primary">%s</strong><span class="total-found-text d-inline ml-1" href="#">%s</span></a>',
                Cake\Routing\Router::URL([
                    'controller' => 'instance',
                    'action' => 'search_all',
                    '?' => [
                        'model' => h($tableName),
                        'search' => h($this->request->getParam('?')['search'] ?? ''),
                        'show_all' => 1
                    ]
                ]),
                __('Load'),
                $remaining,
                __('more results')
            );
        }
        $sections[] = $section;
    }

    if (!empty($ajax)) {
        $sections[] = sprintf('<a class="dropdown-item border-top text-center text-muted p-2" href="%s"><i class="%s mr-2"></i>%s</a>',
            Cake\Routing\Router::URL([
                'controller' => 'instance',
                'action' => 'search_all',
                '?' => [
                    'search' => h($this->request->getParam('?')['search'] ?? '')
                ]
            ]),
            $this->FontAwesome->getClass('search-plus'),
            __('View all results')
        );
    } else {
        echo sprintf('<h2 class="font-weight-light mb-4">%s <span class="text-monospace">%s</span></h2>', __('Global search results for:'), h($this->request->getParam('?')['search'] ?? ''));
    }

    if (!empty($sections)) {
        echo implode('', $sections);
    } else {
        echo sprintf('<span class="dropdown-item p-0 pb-1 text-center">%s</span>', __('- No result -'));
    }
