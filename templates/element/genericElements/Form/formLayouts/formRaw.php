<?php if (!empty($data['description'])) : ?>
    <div class="pb-3 fw-light">
        <?= h($data['description']) ?>
    </div>
<?php endif; ?>
<?= $ajaxFlashMessage ?>
<?= $formCreate ?>
<?= $fieldsString ?>

<?php if (!empty($metaTemplateString)) : ?>
    <?=
    $this->Bootstrap->accordion(
        [
            'class' => 'mb-3'
        ],
        [
            [
                'open' => true,
                'header' => [
                    'title' => __('Meta fields')
                ],
                'body' => $metaTemplateString,
            ],
        ]
    );
    ?>
<?php endif; ?>
<?= $formEnd; ?>