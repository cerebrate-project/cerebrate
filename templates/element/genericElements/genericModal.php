<div class="modal-dialog <?= empty($class) ? '' : h($class) ?>" <?= !empty($staticBackdrop) ? 'data-backdrop="static"' : ''?> role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><?= h($title) ?></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <?= $body ?>
        </div>
        <div class="modal-footer">
            <?php if (empty($noCancel)): ?>
                <button type="button" class="btn btn-secondary cancel-button" data-dismiss="modal"><?= __('Cancel') ?></button>
            <?php endif; ?>
            <?= $actionButton ?>
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
