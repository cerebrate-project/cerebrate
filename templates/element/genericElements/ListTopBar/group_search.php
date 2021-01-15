<?php
    /*
     *  Run a quick filter against the current API endpoint
     *  Result is passed via URL parameters, by default using the searchall key
     *  Valid parameters:
     *  - data: data-* fields
     *  - searchKey: data-search-key, specifying the key to be used (defaults to searchall)
     *  - fa-icon: an icon to use for the lookup $button
     *  - buttong: Text to use for the lookup button
     *  - cancel: Button for quickly removing the filters
     *  - placeholder: optional placeholder for the text field
     *  - id: element ID for the input field - defaults to quickFilterField
     */
    if (!isset($data['requirement']) || $data['requirement']) {
        $button = empty($data['button']) && empty($data['fa-icon']) ? '' : sprintf(
            '<div class="input-group-append"><button class="btn btn-primary" %s id="quickFilterButton-%s">%s%s</button></div>',
            empty($data['data']) ? '' : h($data['data']),
            h($tableRandomValue),
            empty($data['fa-icon']) ? '' : sprintf('<i class="fa fa-%s"></i>', h($data['fa-icon'])),
            empty($data['button']) ? '' : h($data['button'])
        );
        if (!empty($data['cancel'])) {
            $button .= $this->element('/genericElements/ListTopBar/element_simple', array('data' => $data['cancel']));
        }
        $input = sprintf(
            '<input id="quickFilterField-%s" type="text" class="form-control" placeholder="%s" aria-label="%s" style="padding: 2px 6px;" id="%s" data-searchkey="%s" value="%s">',
            h($tableRandomValue),
            empty($data['placeholder']) ? '' : h($data['placeholder']),
            empty($data['placeholder']) ? '' : h($data['placeholder']),
            empty($data['id']) ? 'quickFilterField' : h($data['id']),
            empty($data['searchKey']) ? 'searchall' : h($data['searchKey']),
            empty($data['value']) ? '' : h($data['value'])
        );
        echo sprintf(
            '<div class="input-group" data-table-random-value="%s" style="margin-left: auto;">%s%s</div>',
            h($tableRandomValue),
            $input,
            $button
        );
    }
?>
<script type="text/javascript">
    $(document).ready(function() {
        var controller = '<?= $this->request->getParam('controller') ?>';
        var action = '<?= $this->request->getParam('action') ?>';
        var additionalUrlParams = '';
        <?php
            if (!empty($data['additionalUrlParams'])) {
                echo sprintf(
                    'additionalUrlParams = \'/%s\';',
                    h($data['additionalUrlParams'])
                );
            }
        ?>
        var randomValue = '<?= h($tableRandomValue) ?>';
        $(`#quickFilterButton-${randomValue}`).click(() => {
            doFilter($(this))
        });
        $(`#quickFilterField-${randomValue}`).on('keypress', (e) => {
            if(e.which === 13) {
                const $button = $(this).parent().find(`#quickFilterButton-${randomValue}`)
                doFilter($button)
            }
        });

        function doFilter($button) {
            const encodedFilters = encodeURIComponent($(`#quickFilterField-${randomValue}`).val())
            const url = `/${controller}/${action}${additionalUrlParams}?quickFilter=${encodedFilters}`
            UI.reload(url, $(`#table-container-${randomValue}`), $(`#table-container-${randomValue} table.table`), [{
                node: $button,
                config: {}
            }])
        }
    });
</script>
