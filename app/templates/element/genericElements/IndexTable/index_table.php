<?php
    /*
     *  echo $this->element('/genericElements/IndexTable/index_table', array(
     *      'top_bar' => (
     *          // search/filter bar information compliant with ListTopBar
     *      ),
     *      'data' => array(
                // the actual data to be used
     *      ),
     *      'fields' => array(
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
    if (!$ajax && !empty($data['title'])) {
        echo sprintf('<h2>%s</h2>', h($data['title']));
    }
    if (!$ajax && !empty($data['description'])) {
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
        $paginationData = !empty($data['paginatorOptions']) ? $data['paginatorOptions'] : array();
        echo $this->element(
            '/genericElements/IndexTable/pagination',
            array(
                'paginationOptions' => $paginationData,
                'tableRandomValue' => $tableRandomValue
            )
        );
        if (!$ajax) {
            echo $this->element(
                '/genericElements/IndexTable/pagination_links'
            );
        }
    }
    if (!empty($data['top_bar'])) {
        echo $this->element(
            '/genericElements/ListTopBar/scaffold',
            array(
                'data' => $data['top_bar'],
                'tableRandomValue' => $tableRandomValue
            )
        );
    }
    $rows = '';
    $row_element = isset($data['row_element']) ? $data['row_element'] : 'row';
    $options = isset($data['options']) ? $data['options'] : array();
    $actions = isset($data['actions']) ? $data['actions'] : array();
    $dblclickActionArray = isset($data['actions']) ? $this->Hash->extract($data['actions'], '{n}[dbclickAction]') : array();
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
                array(
                    'k' => $k,
                    'row' => $data_row,
                    'fields' => $data['fields'],
                    'options' => $options,
                    'actions' => $actions,
                    'primary' => $primary,
                    'tableRandomValue' => $tableRandomValue
                )
            )
        );
    }
    $tbody = '<tbody>' . $rows . '</tbody>';
    echo sprintf(
        '<table class="table table-hover" id="index-table-%s">%s%s</table>',
        $tableRandomValue,
        $this->element(
            '/genericElements/IndexTable/headers',
            array(
                'fields' => $data['fields'],
                'paginator' => $this->Paginator,
                'actions' => (empty($data['actions']) ? false : true),
                'tableRandomValue' => $tableRandomValue
            )
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
