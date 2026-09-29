<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Enum\ContentType;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

final class ContentFieldsController extends AdminController
{
    public function edit(string $type): ?Response
    {
        $contentType = ContentType::tryFrom(ucfirst($type)) ?? throw new NotFoundException();
        $schemas = $this->fetchTable('ContentTypeFieldSchemas');
        $schema = $schemas->schemaFor($contentType);
        $this->Authorization->authorize($schema, 'edit');

        if ($this->request->is(['patch', 'post', 'put'])) {
            /** @var array<string, mixed> $form */
            $form = (array) $this->request->getData();

            $schema = $schemas->patchEntity($schema, [
                'field_schema' => $this->decodeFieldSchema($form),
                'subject_type' => $contentType->value,
            ]);
            $schema->subject_type = $contentType->value;

            if ($schemas->save($schema)) {
                $this->Flash->success('Fields saved.');

                return $this->redirect(['action' => 'edit', $type]);
            }
            $this->Flash->error('Please correct the errors below.');
        }

        $this->set([
            'contentType' => $contentType,
            'schema' => $schema,
            'fieldTypes' => $contentType->allowedFieldTypes(),
            'fieldsInUse' => $this->dataUsage($contentType),
        ]);

        return null;
    }

    /**
     * @return array<string, int>
     */
    private function dataUsage(ContentType $type): array
    {
        return $type === ContentType::Pages
            ? $this->fetchTable('Pages')->getBehavior('EditorialContent')->dataUsageByField()
            : $this->fetchTable('Posts')->getBehavior('EditorialContent')->dataUsageByField();
    }
}
