<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Override;

class ErrorController extends Controller
{
    #[Override]
    public function beforeRender(EventInterface $event): void
    {
        $this->viewBuilder()
            ->setTemplatePath('Error')
            ->setLayout('error');
    }
}
