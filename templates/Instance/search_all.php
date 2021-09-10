<?php
    $sections = [];
    foreach ($data as $tableName => $tableResult) {
        $section = '';
        $table = Cake\ORM\TableRegistry::get($tableName);
        $fieldPath = !empty($table->getDisplayField()) ? $table->getDisplayField() : 'id';
        $section .= sprintf('<span class="d-flex text-nowrap px-2 search-container-model">
            <span class="text-uppercase text-muted mr-3 model-text">%s</span>
            <span class="d-flex align-items-center search-container-divider">
                <hr class="m-0"/>
            </span>
        </span>', h($tableName));

        foreach ($tableResult as $entry) {
            $section .= sprintf('<a class="dropdown-item" href="%s">%s</a>',
                Cake\Routing\Router::URL([
                    'controller' => Cake\Utility\Inflector::pluralize($entry->getSource()),
                    'action' => 'view',
                    h($entry['id'])
                ]),
                h($entry[$fieldPath])
            );
        }
        $sections[] = $section;
    }

    if (!empty($sections)) {
        echo implode('', $sections);
    } else {
        echo sprintf('<span class="dropdown-item p-0 pb-1 text-center">%s</span>', __('- No result -'));
    }
