<?php

use Cake\Utility\Inflector;
use Cake\Routing\Router;
?>
<h3><?= h($oldMetaTemplate->name) ?></h3>
<div class="container-fluid">
    <div class="row gx-2">
        <div class="col">
            <div class="panel">
                <h4 class="d-flex justify-content-between align-items-center">
                    <?php
                    $url = Router::url([
                        'action' => 'view',
                        $oldMetaTemplate->id
                    ]);
                    ?>
                    <a href="<?= $url ?>" class="text-decoration-none" target="__blank"><?= __('Version {0}', h($oldMetaTemplate->version)) ?></a>
                    <?=
                    $this->Bootstrap->badge([
                        'text' => __('Data to be migrated over'),
                        'variant' => 'danger',
                        'class' => 'fs-7'
                    ])
                    ?>
                </h4>
                <div>
                    <?=
                    $this->element('MetaTemplates/migrationToNewVersionForm', [
                        'metaTemplate' => $oldMetaTemplate,
                        'entity' => $entity,
                    ])
                    ?>
                </div>
            </div>
        </div>
        <div class="col pt-4 d-flex justify-content-center" style="max-width: 32px;">
            <?= $this->Bootstrap->icon('arrow-alt-circle-right') ?>
        </div>
        <div class="col">
            <div class="panel">
                <h4 class="d-flex justify-content-between align-items-center">
                    <?php
                    $url = Router::url([
                        'action' => 'view',
                        $newMetaTemplate->id
                    ]);
                    ?>
                    <a href="<?= $url ?>" class="text-decoration-none" target="__blank"><?= __('Version {0}', h($newMetaTemplate->version)) ?></a>
                    <?=
                    $this->Bootstrap->badge([
                        'text' => __('Data to be saved'),
                        'variant' => 'success',
                        'class' => 'fs-7'
                    ])
                    ?>
                </h4>
                <div class="to-save-container">
                    <?=
                    $this->element('MetaTemplates/migrationToNewVersionForm', [
                        'metaTemplate' => $newMetaTemplate,
                        'entity' => $entity,
                    ])
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex flex-row-reverse">
        <?=
        $this->Bootstrap->button([
            'text' => __('Update to version {0}', h($newMetaTemplate->version)),
            'variant' => 'success',
            'onclick' => 'submitMigration()',
        ])
        ?>
    </div>
</div>

<?php
echo $this->Html->scriptBlock(sprintf(
    'var csrfToken = %s;',
    json_encode($this->request->getAttribute('csrfToken'))
));
?>

<script>
    $(document).ready(function() {
        const movedMetaTemplateFields = <?= json_encode($movedMetaTemplateFields) ?>;
        const oldMetaTemplateID = <?= h($oldMetaTemplate->id) ?>;
        movedMetaTemplateFields.forEach(metaTemplateId => {
            let validInputPath = `MetaTemplates.${oldMetaTemplateID}.meta_template_fields.${movedMetaTemplateFields}`
            const $inputs = $(`input[field^="${validInputPath}"]`)
            $inputs.addClass('is-valid');
        });
    })

    function submitMigration() {
        const $form = $('.to-save-container form')
        AJAXApi.quickPostForm($form[0]).then((postResult) => {
            if (postResult.additionalData.redirect.url !== undefined) {
                window.location = postResult.additionalData.redirect.url
            }
        })
    }
</script>