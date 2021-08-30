<?php
    $tagsHtml = $this->Tag->tags($entity['tags'], [
        'allTags' => [],
        'picker' => true,
        'editable' => true,
    ]);
?>
<div class="form-group row">
    <div class="col-sm-2 col-form-label"><?= __('Tags') ?></div>
    <div class="col-sm-10">
        <?= $tagsHtml ?>
    </div>
</div>