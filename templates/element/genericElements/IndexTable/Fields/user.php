<?php
    if (!empty($row['user'])) {
        $userId = $this->Hash->extract($row, 'user.id')[0];
        $userName = $this->Hash->extract($row, 'user.username')[0];
        echo $this->Html->link(
            h($userName),
            ['controller' => 'users', 'action' => 'view', $userId]
        );
    }

?>
