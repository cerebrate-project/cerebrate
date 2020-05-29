<?php
$raw_alignments = $this->Hash->extract($row, $field['data_path']);
$alignments = '';
if ($field['scope'] === 'individuals') {
    foreach ($raw_alignments as $alignment) {
        $alignments .= sprintf(
            '<div><span class="font-weight-bold">%s</span> @ %s <a href="#" class="fas fa-trash text-black" onClick="%s"></a></div>',
            h($alignment['type']),
            sprintf(
                '<a href="/organisations/view/%s">%s</a>',
                h($alignment['organisation']['id']),
                h($alignment['organisation']['name'])
            ),
            sprintf(
                "populateAndLoadModal(%s);",
                sprintf(
                    "'/alignments/delete/%s'",
                    $alignment['id']
                )
            )
        );
    }
} else if ($field['scope'] === 'organisations') {
    foreach ($raw_alignments as $alignment) {
        $alignments .= sprintf(
            '<div>[<span class="font-weight-bold">%s</span>] %s <a href="#" class="fas fa-trash text-black" onClick="%s"></a></div>',
            h($alignment['type']),
            sprintf(
                '<a href="/individuals/view/%s">%s</a>',
                h($alignment['individual']['id']),
                h($alignment['individual']['email'])
            ),
            sprintf(
                "populateAndLoadModal(%s);",
                sprintf(
                    "'/alignments/delete/%s'",
                    $alignment['id']
                )
            )
        );
    }
}
echo $alignments;
