<?php
$form = $this->element('genericElements/Form/genericForm', [
    'entity' => null,
    'ajax' => false,
    'raw' => true,
    'data' => [
        'model' => 'MetaTemplate',
        'fields' => [
            [
                'field' => 'update_strategy',
                'type' => 'radio',
                'options' => [
                    ['value' => 'keep_both', 'text' => 'keep_both', 'id' => 'radio_keep_both'],
                    ['value' => 'delete', 'text' => 'delete', 'id' => 'radio_delete'],
                ],
            ]
        ],
        'submit' => [
            'action' => $this->request->getParam('action')
        ],
    ]
]);
?>

<div class="conflict-resolution-picker">
    <div class="mt-3 d-flex justify-content-center">
        <div class="btn-group justify-content-center" role="group" aria-label="Basic radio toggle button group">
            <input type="radio" class="btn-check" name="btnradio" id="btnradio1" autocomplete="off" value="keep_both" checked>
            <label class="btn btn-outline-primary mw-33" for="btnradio1">
                <div>
                    <h5 class="mb-3"><?= __('Keep both template') ?></h5>
                    <ul class="text-start fs-7">
                        <li><?= __('Meta-fields not having conflicts will be migrated to the new  meta-template.') ?></li>
                        <li><?= __('Meta-fields having a conflicts will stay on their current meta-template.') ?></li>
                        <li><?= __('Conflicts can be taken care of manually via the UI.') ?></li>
                    </ul>
                </div>
            </label>

            <input type="radio" class="btn-check" name="btnradio" id="btnradio3" autocomplete="off" value="delete">
            <label class="btn btn-outline-danger mw-33" for="btnradio3">
                <div>
                    <h5 class="mb-3"><?= __('Delete conflicting fields') ?></h5>
                    <ul class="text-start fs-7">
                        <li><?= __('Meta-fields not satisfying the new meta-template definition will be deleted.') ?></li>
                        <li><?= __('All other meta-fields will be upgraded to the new meta-template.') ?></li>
                    </ul>
                </div>
            </label>
        </div>
    </div>
</div>

<div class="d-none conflict-resolution-form-container">
    <?= $form ?>
</div>

<script>
    (function() {
        const $form = $('.conflict-resolution-form-container form')
        const $keep = $form.find('input#radio_keep_both')
        const $delete = $form.find('input#radio_delete')

        $(document).ready(function() {
            $('.conflict-resolution-picker').find('input[type="radio"]').change(function() {
                updateSelected($(this).val())
            })
            updateSelected('keep_both')
        })

        function updateSelected(choice) {
            if (choice == 'keep_both') {
                $keep.prop('checked', true)
            } else if (choice == 'delete') {
                $delete.prop('checked', true)
            }
        }
    }())
</script>