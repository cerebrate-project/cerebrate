<?php
$seed = 'mfb-' . mt_rand();
?>
<div class="d-flex align-items-center">
    <?php
    // $content = sprintf('<a class="btn btn-primary btn-xs">%s</a><a class="btn btn-link btn-xs">%s</a>', $this->Bootstrap->icon('plus'), __('Add another {0}', h($fieldData['label'])));
    $content = sprintf('<a class="btn btn-primary btn-xs">%s</a><a class="btn btn-link btn-xs">%s</a>', $this->Bootstrap->icon('plus'), __('Add another {0}', h($metaTemplateFieldName)));
    $content = sprintf(
        '%s%s',
        $this->Bootstrap->button([
            'nodeType' => 'a',
            'icon' => 'plus',
            'variant' => 'secondary',
            'size' => 'xs',
        ]),
        $this->Bootstrap->button([
            'nodeType' => 'a',
            // 'text' => __('Add another {0}', h($fieldData['label'])),
            'text' => __('Add another {0}', h($metaTemplateFieldName)),
            'variant' => 'link',
            'class' => ['link-secondary'],
            'size' => 'xs',
        ])
    );
    ?>
    <?=
    $this->Bootstrap->button([
        'id' => $seed,
        'html' => $content,
        'variant' => 'link',
        'size' => 'xs',
    ]);
    ?>
</div>

<script>
    (function() {
        $('#<?= $seed ?>').click(addNewField)

        function addNewField() {
            const $clicked = $(this);
            let $lastInputContainer = $clicked.closest('.multi-metafields-container').children().not('.template-container').find('input').last().closest('.multi-metafield-container')
            if ($lastInputContainer.length == 0) {
                $lastInputContainer = $clicked.closest('.multi-metafields-container').find('input').last().closest('.multi-metafield-container')
            }
            const $clonedContainer = $lastInputContainer.clone()
            $clonedContainer
                .removeClass('has-error')
                .find('.error-message ').remove()
            const $clonedInput = $clonedContainer.find('input, select')
            if ($clonedInput.length > 0) {
                const injectedTemplateId = $clicked.closest('.multi-metafields-container').find('.new-metafield').length
                $clonedInput.addClass('new-metafield')
                adjustClonedInputAttr($clonedInput, injectedTemplateId)
                $clonedContainer.insertAfter($lastInputContainer)
            }
        }

        function adjustClonedInputAttr($input, injectedTemplateId) {
            let explodedPath = $input.attr('field').split('.').splice(0, 5)
            explodedPath.push('new', injectedTemplateId)
            dottedPathStr = explodedPath.join('.')
            brackettedPathStr = explodedPath.map((elem, i) => {
                return i == 0 ? elem : `[${elem}]`
            }).join('')
            $input.attr('id', dottedPathStr)
                .attr('field', dottedPathStr)
                .attr('name', brackettedPathStr)
                .val('')
                .removeClass('is-invalid')
        }
        // function addNewField() {
        //     const $clicked = $(this);
        //     const $lastInputContainer = $clicked.closest('.multi-metafields-container').children().not('.template-container').find('input').last().closest('.multi-metafield-container')
        //     const $templateContainer = $clicked.closest('.multi-metafields-container').find('.template-container').children()
        //     const $clonedContainer = $templateContainer.clone()
        //     $clonedContainer.removeClass('d-none', 'template-container')
        //     const $clonedInput = $clonedContainer.find('input, select')
        //     if ($clonedInput.length > 0) {
        //         const injectedTemplateId = $clicked.closest('.multi-metafields-container').find('.new-metafield').length
        //         $clonedInput.addClass('new-metafield')
        //         adjustClonedInputAttr($clonedInput, injectedTemplateId)
        //         $clonedContainer.insertAfter($lastInputContainer)
        //     }
        // }

        // function adjustClonedInputAttr($input, injectedTemplateId) {
        //     let explodedPath = $input.attr('field').split('.').splice(0, 5)
        //     explodedPath.push('new', injectedTemplateId)
        //     dottedPathStr = explodedPath.join('.')
        //     brackettedPathStr = explodedPath.map((elem, i) => {
        //         return i == 0 ? elem : `[${elem}]`
        //     }).join('')
        //     $input.attr('id', dottedPathStr)
        //         .attr('field', dottedPathStr)
        //         .attr('name', brackettedPathStr)
        //         .val('')
        // }
    })()
</script>