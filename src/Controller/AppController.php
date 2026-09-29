<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\SiteTheme;
use Authentication\Controller\Component\AuthenticationComponent;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;

/**
 * @property AuthenticationComponent $Authentication
 */
class AppController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');
    }

    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);
        $settings = $this->fetchTable('Settings')->all();
        $this->set('siteTitle', $settings['site_title']);
        $this->set('tagline', $settings['tagline']);
        $this->set('siteTheme', SiteTheme::fromValue($settings['theme']));
        $this->set('workspacePath', $this->workspacePath());
    }

    protected function workspacePath(): string
    {
        $slug = $this->getRequest()->getParam('workspaceSlug');

        return is_string($slug) && $slug !== '' ? '/' . $slug : '';
    }

    protected function stringInput(string $key): string
    {
        $value = $this->request->getData($key);

        return is_string($value) ? trim($value) : '';
    }
}
