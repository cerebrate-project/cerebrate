<?php
    use Cake\Routing\Router;

    $seed = 'sb-' . mt_rand();
    $icon = $entry['icon'] ?? '';
    $label = $entry['label'] ?? '';
    $name = $entry['name'] ?? '';
    $active = false;

    $url = $entry['url'];

    $currentURL = Router::url(null);
    if ($url == $currentURL) {
        $active = true;
    }

    echo $this->Bootstrap->button([
        'nodeType' => 'a',
        'text' => h($label),
        'title' => h($name),
        'variant' => 'dark',
        'outline' => !$active,
        'size' => 'sm',
        'icon' => h($icon),
        'class' => ['mb-1'],
        'params' => [
            'href' => h($url),
        ]
    ]);
?>
