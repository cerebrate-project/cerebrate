<ul>
    <?php foreach ($data as $table => $tableResult): ?>
        <li><strong><?= h($table) ?></strong></li>
        <ul>
            <?php foreach ($tableResult as $entry): ?>
                <li><strong><?= h($entry['id']) ?></strong></li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</ul>