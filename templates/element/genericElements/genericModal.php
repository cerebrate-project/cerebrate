<div class="modal-dialog <?= empty($class) ? '' : h($class) ?>" role="document">
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
            <?= $actionButton ?>
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
