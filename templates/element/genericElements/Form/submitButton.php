<?php
    if ($ajax) {
        echo sprintf(
            '%s',
            sprintf(
                '<button id="submitButton" class="btn btn-primary" onClick="%s" autofocus>%s</button>',
                "$('#form-" . h($formRandomValue) . "').submit()",
                __('Submit')
            )
        );
    } else {
        echo $this->Form->button(empty($text) ? __('Submit') : h($text), [
            'class' => 'btn btn-' . (empty($type) ? 'primary' : h($type)),
            'type' => 'submit'
        ]);
    }
?>
