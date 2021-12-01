<form>
    <div class="mt-3 d-flex justify-content-center">
        <div class="btn-group justify-content-center" role="group" aria-label="Basic radio toggle button group">
            <input type="radio" class="btn-check" name="btnradio" id="btnradio1" autocomplete="off" checked>
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

            <input type="radio" class="btn-check" name="btnradio" id="btnradio3" autocomplete="off">
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
</form>