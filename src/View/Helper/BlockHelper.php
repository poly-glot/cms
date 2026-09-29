<?php

declare(strict_types=1);

namespace App\View\Helper;

use App\Service\Page\BlockExpander;
use Cake\View\Helper;
use Cake\View\View;

/**
 * View seam for expanding block references inside page body HTML.
 *
 * @extends Helper<View>
 */
final class BlockHelper extends Helper
{
    /**
     * Expand `<div data-block="ID">` placeholders into semantic HTML.
     *
     * The returned string is intentionally raw, pre-escaped HTML: BlockExpander
     * runs htmlspecialchars on every block field, so no further escaping is
     * needed (or wanted) at the template's echo site.
     */
    public function expand(?string $body): string
    {
        return new BlockExpander()->expand($body);
    }
}
