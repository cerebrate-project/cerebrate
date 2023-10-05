<?php
echo sprintf(
    '<a class="btn btn-primary" href="%s/instance/downloadTopology">Download markdown</a>',
    h($baseurl)
);
echo $this->element('genericElements/mermaid', [
    'data' => $data,
]);
