<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Enum\SiteTheme;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

final class AppearanceController extends AdminController
{
    use AdminOnlyTrait;

    public function index(): void
    {
        $this->set([
            'themes' => SiteTheme::cases(),
            'active' => SiteTheme::fromValue($this->fetchTable('Settings')->value('theme')),
        ]);
    }

    public function activate(): ?Response
    {
        $this->request->allowMethod('post');
        $value = $this->request->getData('theme');
        $theme = is_string($value) ? SiteTheme::tryFrom($value) : null;
        if ($theme === null) {
            throw new NotFoundException();
        }

        $this->fetchTable('Settings')->writeMany(['theme' => $theme->value]);
        $this->Flash->success(sprintf('%s theme activated.', $theme->label()));

        return $this->redirect(['action' => 'index']);
    }
}
