<?php
$form = $this->element('genericElements/Form/genericForm', [
    'entity' => null,
    'ajax' => false,
    'raw' => true,
    'data' => [
        'submit' => [
            'action' => $this->request->getParam('action')
        ]
    ]
]);
$formHTML = sprintf('<div class="d-none">%s</div>', $form);
$bodyMessage = !empty($deletionText) ? h($deletionText) : __(
    'Are you sure you want to detach {2} #{3} from {0} #{1}?',
    h($data[0]['model']),
    h($data[0]['id']),
    h($data[1]['model']),
    h($data[1]['id'])
);

$bodyHTML = sprintf('%s%s', $formHTML, $bodyMessage);

echo $this->Bootstrap->modal([
    'size' => 'lg',
    'title' => !empty($deletionTitle) ? $deletionTitle : __(
        'Detach {0} from {1}',
        h($data[0]['model']),
        h($data[1]['model']),
    ),
    'type' => 'confirm',
    'confirmButton' => [
        'text' => !empty($deletionConfirm) ? $deletionConfirm : __('Detach'),
        'variant' => 'danger',
    ],
    'bodyHtml' => $bodyHTML,
]);
?>
