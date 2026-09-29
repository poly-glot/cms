<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Table\SettingsTable;
use Cake\Http\Response;

final class SettingsController extends AdminController
{
    use AdminOnlyTrait;

    public function index(): void
    {
        $this->set('settings', $this->fetchTable('Settings')->all());
    }

    public function save(): ?Response
    {
        $this->request->allowMethod('post');

        $values = [];
        foreach (array_keys(SettingsTable::DEFAULTS) as $key) {
            $posted = $this->request->getData($key);
            if ($posted !== null) {
                $values[$key] = is_scalar($posted) ? (string) $posted : '';
            }
        }

        $this->fetchTable('Settings')->writeMany($values);
        $this->Flash->success('Settings saved.');

        return $this->redirect(['action' => 'index']);
    }
}
