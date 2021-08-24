<?php

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Hash;

class TagHelper extends Helper
{
    public $helpers = [
        'Bootstrap',
        'TextColour',
        'FontAwesome',
        'Tags.Tag',
    ];

    protected $_defaultConfig = [
        'default_colour' => '#983965',
    ];

    public function control(array $options = [])
    {
        return $this->Tag->control($options);
    }

    public function picker(array $options = [])
    {
        $optionsHtml = '';
        foreach ($options['allTags'] as $i => $tag) {
            $optionsHtml .= $this->Bootstrap->genNode('option', [
                'value' => h($tag['text']),
                'data-colour' => h($tag['colour']),
            ], h($tag['text']));
        }
        $html = $this->Bootstrap->genNode('select', [
            'class' => ['tag-input', 'd-none'],
            'multiple' => '',
        ], $optionsHtml);
        $html .= $this->Bootstrap->button([
            'size' => 'sm',
            'icon' => 'plus',
            'variant' => 'secondary',
            'class' => ['badge'],
            'params' => [
                'onclick' => 'createTagPicker(this)',
            ]
        ]);
        return $html;
    }

    public function tags(array $options = [])
    {
        $tags = !empty($options['tags']) ? $options['tags'] : [];
        $html = '<div class="tag-container my-1">';
        $html .= '<div class="tag-list d-inline-block">';
        foreach ($tags as $tag) {
            if (is_array($tag)) {
                $html .= $this->tag($tag);
            } else {
                $html .= $this->tag([
                    'name' => $tag
                ]);
            }
        }
        $html .= '</div>';
        
        if (!empty($options['picker'])) {
            $html .= $this->picker($options);
        }
        $html .= '</div>';
        return $html;
    }

    public function tag(array $tag)
    {
        $tag['colour'] = !empty($tag['colour']) ? $tag['colour'] : $this->getConfig()['default_colour'];
        $textColour = $this->TextColour->getTextColour(h($tag['colour']));
        $deleteButton = $this->Bootstrap->button([
            'size' => 'sm',
            'icon' => 'times',
            'class' => ['ml-1', 'border-0', "text-${textColour}"],
            'variant' => 'text',
            'title' => __('Delete tag'),
        ]);
        
        $html = $this->Bootstrap->genNode('span', [
            'class' => [
                'tag',
                'badge',
                'mx-1',
                'align-middle',
            ],
            'title' => h($tag['name']),
            'style' => sprintf('color:%s; background-color:%s', $textColour, h($tag['colour'])),
        ], h($tag['name']) . $deleteButton);
        return $html;
    }
}
