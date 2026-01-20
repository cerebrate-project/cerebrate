<?php
    if (!empty($ajax)) {
        echo sprintf(
            '%s',
            sprintf(
                '<button id="submitButton" class="btn btn-primary %s" data-form-id="%s" type="button" autofocus>%s</button>',
                !empty($hidden) ? 'd-none' : '',
                '#form-' . h($formRandomValue),
                __('Submit')
            )
        );
    } else {
        echo $this->Form->button(empty($text) ? __('Submit') : h($text), [
            'class' => sprintf('%s btn btn-' . (empty($type) ? 'primary' : h($type)), !empty($hidden) ? 'd-none' : ''),
            'type' => 'submit',
            'data-form-id' => '#form-' . h($formRandomValue)
        ]);
    }
?>
