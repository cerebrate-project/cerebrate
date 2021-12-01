<table class="table">
    <thead>
        <tr>
            <th scope="col"><?= __('Field name') ?></th>
            <th scope="col"><?= __('Conflict') ?></th>
            <th scope="col"><?= __('Automatic Resolution') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($updateableTemplate['conflicts'] as $fieldName => $fieldConflict) : ?>
            <?php foreach ($fieldConflict['conflicts'] as $conflict) : ?>
                <tr>
                    <th scope="row"><?= h($fieldName) ?></th>
                    <td>
                        <?= h($conflict) ?>
                    </td>
                    <td>
                        <?php
                            echo $this->Bootstrap->badge([
                                'text' => __('Affected meta-fields will be removed'),
                                'variant' => 'danger',
                            ])
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </tbody>
</table>