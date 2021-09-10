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
        </span>', h($tableName));

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
            $section .= sprintf('<span class="total-found d-block pr-2"><strong class="total-found-number text-primary">%s</strong><span class="total-found-text d-inline ml-1" href="#">%s</span></span>',
                $remaining,
                __('more results')
            );
        }
        $sections[] = $section;
    }

    if (!empty($sections)) {
        echo implode('', $sections);
    } else {
        echo sprintf('<span class="dropdown-item p-0 pb-1 text-center">%s</span>', __('- No result -'));
    }
