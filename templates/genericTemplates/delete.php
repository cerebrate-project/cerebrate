<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><?= __('Delete {0}', h(Cake\Utility\Inflector::singularize(Cake\Utility\Inflector::humanize($this->request->getParam('controller'))))) ?></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <?php if (empty($deletionText)): ?>
                <p><?= __('Are you sure you want to delete {0} #{1}?', h(Cake\Utility\Inflector::singularize($this->request->getParam('controller'))), h($id)) ?></p>
            <?php else: ?>
                <p><?= h($deletionText) ?></p>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <?= $this->Form->postLink(
                'Delete',
                (empty($postLinkParameters) ? ['action' => 'delete', $id] : $postLinkParameters),
                ['class' => 'btn btn-primary button-execute', 'id' => 'submitButton']
                )
            ?>
            <button type="button" class="btn btn-secondary cancel-button" data-dismiss="modal"><?= __('Cancel') ?></button>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).keydown(function(e) {
        if(e.which === 13 && e.ctrlKey) {
            $('.button-execute').click();
        }
    });
</script>
