<?php
$raw_alignments = $this->Hash->extract($row, $field['data_path']);
$alignments = '';
$canRemove = $this->request->getParam('prefix') !== 'Open';
if ($field['scope'] === 'individuals') {
    foreach ($raw_alignments as $alignment) {
        $canEdit = in_array($alignment->individual_id, $editableIds);
        $alignmentEntryHtml = $this->Bootstrap->node('span', ['class' => ['fw-bold']], h($alignment['type']));
        $alignmentEntryHtml .= ' @ ' . $this->Bootstrap->node('span', ['class' => ['ms-1']], sprintf(
            '<a href="%s/organisations/view/%s">%s</a>',
            $baseurl,
            h($alignment['organisation']['id']),
            h($alignment['organisation']['name'])
        ),);
        if ($canRemove && !empty($canEdit)) {
            $alignmentEntryHtml .= $this->Bootstrap->button([
                'icon' => 'trash',
                'variant' => 'link',
                'class' => ['ms-1', 'p-0'
                ],
                'onclick' => sprintf(
                    "UI.submissionModalForSinglePage(%s);",
                    sprintf(
                        "'/alignments/delete/%s'",
                        $alignment['id']
                    )
                )
            ]);
        }
        $alignments .= sprintf('<div>%s</div>', $alignmentEntryHtml);
    }
} else if ($field['scope'] === 'organisations') {
    foreach ($raw_alignments as $alignment) {
        $canEdit = in_array($alignment->organisation_id, $editableIds);
        $alignmentEntryHtml = '[' .  $this->Bootstrap->node('span', ['class' => ['fw-bold']], h($alignment['type'])) . ']';
        $alignmentEntryHtml .= $this->Bootstrap->node('span', ['class' => ['ms-1']], sprintf(
            '<a href="%s/organisations/view/%s">%s</a>',
            $baseurl,
            h($alignment['individual']['id']),
            h($alignment['individual']['email'])
        ),);
        if ($canRemove && !empty($canEdit)) {
            $alignmentEntryHtml .= $this->Bootstrap->button([
                'icon' => 'trash',
                'variant' => 'link',
                'class' => ['ms-1', 'p-0'],
                'onclick' => sprintf(
                    "UI.submissionModalForSinglePage(%s);",
                    sprintf(
                        "'/alignments/delete/%s'",
                        $alignment['id']
                    )
                )
            ]);
        }
        $alignments .= sprintf('<div>%s</div>', $alignmentEntryHtml);
    }
}
echo $alignments;
