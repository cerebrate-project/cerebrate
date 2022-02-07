<?php

use Cake\ORM\TableRegistry;

$bookmarks = !empty($loggedUser->user_settings_by_name['ui.bookmarks']['value']) ? json_decode($loggedUser->user_settings_by_name['ui.bookmarks']['value'], true) : [];
$this->userSettingsTable = TableRegistry::getTableLocator()->get('UserSettings');
?>

<h3>
    <?= $this->Bootstrap->icon('bookmark', [
        'class' => ['fa-fw']
    ]); ?>
    <?= __('Bookmarks') ?>
</h3>
<div class="row">
    <?php if (!empty($bookmarks)) : ?>
        <ul class="col-sm-12 col-md-10 col-l-8 col-xl-8 mb-3">
            <?php foreach ($bookmarks as $bookmark) : ?>
                <li class="list-group-item">
                    <?php if ($this->userSettingsTable->validURI($bookmark['url'])): ?>
                        <a href="<?= h($bookmark['url']) ?>" class="w-bold">
                            <?= h($bookmark['label']) ?>
                        </a>
                    <?php else: ?>
                        <span class="w-bold">
                            <?= h($bookmark['url']) ?>
                        </span>
                    <?php endif; ?>
                    <span class="ms-3 fw-light"><?= h($bookmark['name']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="fw-light"><?= __('No bookmarks') ?></p>
    <?php endif; ?>
</div>

<h3>
    <?= $this->Bootstrap->icon('chart-bar', [
        'class' => ['fa-fw']
    ]); ?>
    <?= __('Activity') ?>
</h3>
<div class="row">
    <?php foreach ($statistics as $modelName => $statistics) : ?>
        <div class="col-sm-6 col-md-5 col-l-4 col-xl-3 mb-3">
            <?php
            $exploded = explode('.', $modelName);
            $modelForDisplay = $exploded[count($exploded) - 1];
            $panelTitle = $this->Html->link(
                h($modelForDisplay),
                $this->Url->build([
                    'controller' => $modelForDisplay,
                    'action' => 'index',
                ]),
                ['class' => 'text-white text-decoration-none fw-light stretched-link']
            );
            echo $this->element('widgets/highlight-panel', [
                'titleHtml' => $panelTitle,
                'number' => $statistics['amount'],
                'variation' => $statistics['variation'] ?? '',
                'chartData' => $statistics['timeline'] ?? []
            ]);
            ?>
        </div>
    <?php endforeach ?>
</div>
