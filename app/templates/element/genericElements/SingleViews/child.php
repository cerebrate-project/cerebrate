<?php
/*
 * create single view child index
 *
 */
    $randomId = Cake\Utility\Security::randomString(8);
    sprintf(
        '<div class="card">%s</div>',
        sprintf(
            '<div class="card-header" id="heading-%s"><h5 class="mb0">%s</h5></div>',
            $randomId,
            sprintf(
                '<button class="btn btn-link" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">%s</button>',
                h($title)
            )
        ),
        sprintf(
            '<div class="collapse %s" id="view-child-%s" data-parent="#accordion" labelledby="heading-%s"><div class="card-body>"%s</div></div>',
            empty($collapsed) ? 'show' : 'collapsed',
            $randomId,
            $randomId,
            h($url)
        ),
        empty($loadOn) ? 'ready' : h($loadOn)
    );
