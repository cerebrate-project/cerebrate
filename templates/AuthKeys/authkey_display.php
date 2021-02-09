<?php
echo $this->element('genericElements/genericModal', [
    'title' => __('Authkey created'),
    'body' => sprintf(
        '<p>%s</p><p>%s</p><p>%s</p>',
        __('Please make sure that you note down the authkey below, this is the only time the authkey is shown in plain text, so make sure you save it. If you lose the key, simply remove the entry and generate a new one.'),
        __('Cerebrate will use the first and the last 4 digit for identification purposes.'),
        sprintf('%s: <span class="font-weight-bold">%s</span>', __('Authkey'), h($entity->authkey_raw))
    ),
    'actionButton' => sprintf('<button" class="btn btn-primary" data-dismiss="modal">%s</button>', __('I have noted down my key, take me back now')),
    'noCancel' => true,
    'staticBackdrop' => true,
]);
