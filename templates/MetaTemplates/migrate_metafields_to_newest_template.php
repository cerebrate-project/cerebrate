<?php

use Cake\Utility\Inflector;
use Cake\Routing\Router;

$urlNewestMetaTemplate = Router::url([
    'controller' => 'metaTemplates',
    'action' => 'view',
    $newestMetaTemplate->id
]);

$bodyHtml = '';
$bodyHtml .= sprintf('<div><span>%s: </span><span class="font-monospace">%s</span></div>', __('Current version'), h($oldMetaTemplate->version));
$bodyHtml .= sprintf('<div><span>%s: </span><a href="%s" target="_blank" class="font-monospac">%s</a></div>', __('Newest version'), $urlNewestMetaTemplate, h($newestMetaTemplate->version));
$bodyHtml .= sprintf('<h4 class="my-2">%s</h4>', __('{0} Entities with meta-fields for the meta-template version <span class="font-monospace">{1}</span>', h($entityCount), h($oldMetaTemplate->version)));

// debug($conflictingEntities);

if (empty($conflictingEntities)) {
    $bodyHtml .= $this->Bootstrap->alert([
        'text' => __('All entities can updated automatically', count($conflictingEntities)),
        'variant' => 'success',
        'dismissible' => false,
    ]);
} else {
    $bodyHtml .= $this->Bootstrap->alert([
        'html' => sprintf(
            '<ul>%s%s</ul>',
            $this->Bootstrap->node('li', [], __('{0} entities can be updated automatically', $entityCount - count($conflictingEntities))),
            $this->Bootstrap->node('li', [], __('{0} entities cannot be updated automatically and require manual migration', count($conflictingEntities)))
        ),
        'variant' => 'warning',
        'dismissible' => false,
    ]);
    $bodyHtml .= '<ul>';
    foreach ($conflictingEntities as $entity) {
        $url = Router::url([
            'controller' => 'metaTemplates',
            'action' => 'migrateOldMetaTemplateToNewestVersionForEntity',
            $oldMetaTemplate->id,
            $entity->id,
        ]);
        $bodyHtml .= sprintf(
            '<li><a href="%s" target="_blank">%s</a> <span class="fw-light">%s<span></li>',
            $url,
            __('{0}::{1}', h(Inflector::humanize($oldMetaTemplate->scope)), $entity->id),
            __('has {0} meta-fields to update', count($entity->meta_fields))
        );
    }
    if (count($conflictingEntities) > 10) {
        $bodyHtml .= sprintf('<li class="list-inline-item fw-light fs-7">%s</li>', __('{0} more entities', h(10 - count($conflictingEntities))));
    }
    $bodyHtml .= '</ul>';
}
$form = sprintf(
    '<div class="d-none hidden-form-container">%s%s</div>',
    $this->Form->create(null, [
        'url' => [
            'controller' => 'MetaTemplates',
            'action' => 'migrateMetafieldsToNewestTemplate',
            $oldMetaTemplate->id,
        ]
    ]),
    $this->Form->end()
);
$bodyHtml .= $form;

$title = __('{0} has a new meta-template and meta-fields to be updated', sprintf('<i class="me-1">%s</i>', h($oldMetaTemplate->name)));
if (!empty($ajax)) {
    echo $this->Bootstrap->modal([
        'titleHtml' => $title,
        'bodyHtml' => $bodyHtml,
        'size' => 'lg',
        'type' => 'confirm',
        'confirmButton' => [
            'text' => __('Migrate meta-fields'),
            'variant' => 'success',
        ],
        'confirmFunction' => 'migrateMetafieldsToNewestTemplate',
    ]);
} else {
    echo $this->Bootstrap->node('h1', [], $title);
    echo $bodyHtml;
    echo $this->Bootstrap->button([
        'text' => __('Migrate meta-fields'),
        'variant' => 'success',
        'onclick' => '$(".hidden-form-container form").submit()',
    ]);
}
?>

<script>
    function migrateMetafieldsToNewestTemplate(modalObject, tmpApi) {
        const $form = modalObject.$modal.find('form')
        return tmpApi.postForm($form[0]).catch((errors) => {
            const formHelper = new FormValidationHelper($form[0])
            const errorHTMLNode = formHelper.buildValidationMessageNode(errors, true)
            modalObject.$modal.find('div.form-error-container').append(errorHTMLNode)
            return errors
        })
    }
</script>