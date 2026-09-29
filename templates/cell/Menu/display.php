<?php
/**
 * @var App\View\AppView $this
 * @var list<array{label: string, url: string, target: string, children: array<mixed>}> $links
 */
if ($links === []) {
    return;
}

/** @var callable(array<mixed>): string $renderList */
$renderList = static function (array $items) use (&$renderList): string {
    $html = '<ul class="site-nav__list">';
    foreach ($items as $item) {
        $target = $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '';
        $html .= '<li class="site-nav__item">';
        $html .= '<a class="site-nav__link" href="' . h($item['url']) . '"' . $target . '>' . h($item['label']) . '</a>';
        if ($item['children'] !== []) {
            $html .= $renderList($item['children']);
        }
        $html .= '</li>';
    }

    return $html . '</ul>';
};
?>
<nav class="site-nav" aria-label="Primary">
    <?php echo $renderList($links); ?>
</nav>
