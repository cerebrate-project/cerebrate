
<?php
use Cake\Utility\Inflector;
use Cake\Routing\Router;

$urlNewestMetaTemplate = Router::url([
    'controller' => 'metaTemplates',
    'action' => 'view',
    $newestMetaTemplate->id
]);

$bodyHtml = '';
$bodyHtml .= sprintf('<div><span>%s: </span><span class="fw-bold">%s</span></div>', __('Current version'), h($metaTemplate->version));
$bodyHtml .= sprintf('<div><span>%s: </span><a href="%s" target="_blank" class="fw-bold">%s</a></div>', __('Newest version'), $urlNewestMetaTemplate, h($newestMetaTemplate->version));
$bodyHtml .= sprintf('<h4 class="my-2">%s</h4>', __('Entities with meta-fields to be updated:'));

$bodyHtml .= '<ul>';
foreach ($entities as $entity) {
    $url = Router::url([
        'controller' => Inflector::pluralize($metaTemplate->scope),
        'action' => 'view',
        $entity->id
    ]);
    $bodyHtml .= sprintf(
        '<li><a href="%s" target="_blank">%s</a> <span class="fw-light">%s<span></li>',
        $url,
        __('{0}::{1}', h(Inflector::humanize($metaTemplate->scope)), $entity->id),
        __('has {0} meta-fields to update', count($entity->meta_fields))
    );
}
$bodyHtml .= '</ul>';

echo $this->Bootstrap->modal([
    'titleHtml' => __('{0} has a new meta-template and meta-fields to be updated', sprintf('<i class="me-1">%s</i>', h($metaTemplate->name))),
    'bodyHtml' => $bodyHtml,
    'size' => 'lg',
    'type' => 'ok-only',
]);
?>