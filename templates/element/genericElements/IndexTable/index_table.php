<?php
    /*
     *  echo $this->element('/genericElements/IndexTable/index_table', [
     *      'top_bar' => (
     *          // search/filter bar information compliant with ListTopBar
     *      ),
     *      'data' => [
                // the actual data to be used
     *      ),
     *      'fields' => [
     *          // field list with information for the paginator, the elements used for the individual cells, etc
     *      ),
     *      'title' => optional title,
     *      'description' => optional description,
     *      'primary_id_path' => path to each primary ID (extracted and passed as $primary to fields)
     *  ));
     *
     */
    $tableRandomValue = Cake\Utility\Security::randomString(8);
    echo '<div id="table-container-' . h($tableRandomValue) . '">';
    if (!empty($data['title'])) {
        echo sprintf('<h2>%s</h2>', h($data['title']));
    }
    if (!empty($data['description'])) {
        echo sprintf(
            '<div>%s</div>',
            empty($data['description']) ? '' : h($data['description'])
        );
    }
    if (!empty($data['html'])) {
        echo sprintf('<div>%s</div>', $data['html']);
    }
    $skipPagination = isset($data['skip_pagination']) ? $data['skip_pagination'] : 0;
    if (!$skipPagination) {
        $paginationData = !empty($data['paginatorOptions']) ? $data['paginatorOptions'] : [];
        echo $this->element(
            '/genericElements/IndexTable/pagination',
            [
                'paginationOptions' => $paginationData,
                'tableRandomValue' => $tableRandomValue
            ]
        );
        echo $this->element(
            '/genericElements/IndexTable/pagination_links'
        );
    }
    if (!empty($data['top_bar'])) {
        echo $this->element(
            '/genericElements/ListTopBar/scaffold',
            [
                'data' => $data['top_bar'],
                'tableRandomValue' => $tableRandomValue
            ]
        );
    }
    $rows = '';
    $row_element = isset($data['row_element']) ? $data['row_element'] : 'row';
    $options = isset($data['options']) ? $data['options'] : [];
    $actions = isset($data['actions']) ? $data['actions'] : [];
    if ($this->request->getParam('prefix') === 'Open') {
        $actions = [];
    }
    $dblclickActionArray = !empty($actions) ? $this->Hash->extract($actions, '{n}[dbclickAction]') : [];
    $dbclickAction = '';
    foreach ($data['data'] as $k => $data_row) {
        $primary = null;
        if (!empty($data['primary_id_path'])) {
            $primary = $this->Hash->extract($data_row, $data['primary_id_path'])[0];
        }
        if (!empty($dblclickActionArray)) {
            $dbclickAction = sprintf("changeLocationFromIndexDblclick(%s)", $k);
        }
        $rows .= sprintf(
            '<tr data-row-id="%s" %s %s class="%s %s">%s</tr>',
            h($k),
            empty($dbclickAction) ? '' : 'ondblclick="' . $dbclickAction . '"',
            empty($primary) ? '' : 'data-primary-id="' . $primary . '"',
            empty($data['row_modifier']) ? '' : h($data['row_modifier']($data_row)),
            empty($data['class']) ? '' : h($data['row_class']),
            $this->element(
                '/genericElements/IndexTable/' . $row_element,
                [
                    'k' => $k,
                    'row' => $data_row,
                    'fields' => $data['fields'],
                    'options' => $options,
                    'actions' => $actions,
                    'primary' => $primary,
                    'tableRandomValue' => $tableRandomValue
                ]
            )
        );
    }
    $tbody = '<tbody>' . $rows . '</tbody>';
    echo sprintf(
        '<table class="table table-hover" id="index-table-%s">%s%s</table>',
        $tableRandomValue,
        $this->element(
            '/genericElements/IndexTable/headers',
            [
                'fields' => $data['fields'],
                'paginator' => $this->Paginator,
                'actions' => (empty($actions) ? false : true),
                'tableRandomValue' => $tableRandomValue
            ]
        ),
        $tbody
    );
    if (!$skipPagination) {
        echo $this->element('/genericElements/IndexTable/pagination_counter', $paginationData);
        echo $this->element('/genericElements/IndexTable/pagination_links');
    }
    echo '</div>';
?>
<script type="text/javascript">
    $(document).ready(function() {
        $('.privacy-toggle').on('click', function() {
            var $privacy_target = $(this).parent().find('.privacy-value');
            if ($(this).hasClass('fa-eye')) {
                $privacy_target.text($privacy_target.data('hidden-value'));
                $(this).removeClass('fa-eye');
                $(this).addClass('fa-eye-slash');
            } else {
                $privacy_target.text('****************************************');
                $(this).removeClass('fa-eye-slash');
                $(this).addClass('fa-eye');
            }
        });
    });
</script>
