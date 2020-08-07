<h4><?= __('Authkey created'); ?></h4>
<p><?= __('Please make sure that you note down the authkey below, this is the only time the authkey is shown in plain text, so make sure you save it. If you lose the key, simply remove the entry and generate a new one.'); ?></p>
<p><?=__('Cerebrate will use the first and the last 4 digit for identification purposes.')?></p>
<p><?= sprintf('%s: <span class="text-weight-bold">%s</span>', __('Authkey'), h($entity->authkey_raw)) ?></p>
<a href="<?= $referer ?>" class="btn btn-primary"><?= __('I have noted down my key, take me back now') ?></a>
