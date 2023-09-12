<?php
$alignments = '';
$extracted = $data;
if (!empty($field['path'])) {
    if (strpos('.', $field['path']) !== false) {
        $extracted = Cake\Utility\Hash::extract($data, empty($field['path']) ? 'individual' : $field['path']);
    } else {
        $extracted = $data[$field['path']];
    }
}
if ($field['scope'] === 'individuals') {
    foreach ($extracted['alignments'] as $alignment) {
        $alignmentEntryHtml = $this->Bootstrap->node('span', ['class' => ['fw-bold']], h($alignment['type']));
        $alignmentEntryHtml .= ' @ ' . $this->Bootstrap->node('span', ['class' => ['ms-1']], sprintf(
            '<a href="%s/organisations/view/%s">%s</a>',
            $baseurl,
            h($alignment['organisation']['id']),
            h($alignment['organisation']['name'])
        ),);
        if (!empty($canEdit)) {
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
} else if ($field['scope'] === 'organisations') {
    foreach ($extracted['alignments'] as $alignment) {
        $alignmentEntryHtml = '[' .  $this->Bootstrap->node('span', ['class' => ['fw-bold']], h($alignment['type'])) . ']';
        $alignmentEntryHtml .= $this->Bootstrap->node('span', ['class' => ['ms-1']], sprintf(
            '<a href="%s/organisations/view/%s">%s</a>',
            $baseurl,
            h($alignment['individual']['id']),
            h($alignment['individual']['email'])
        ),);
        if (!empty($canEdit)) {
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
echo sprintf('<div class="alignments-list">%s</div>',  $alignments);
if (!empty($canEdit)) {
    echo sprintf(
        '<div class="alignments-add-container"><button class="alignments-add-button btn btn-primary btn-sm" onclick="%s">%s</button></div>',
        sprintf(
            "UI.submissionModalForSinglePage('/alignments/add/%s/%s');",
            h($field['scope']),
            h($extracted['id'])
        ),
        $field['scope'] === 'individuals' ? __('Add organisation') : __('Add individual')
    );
}
