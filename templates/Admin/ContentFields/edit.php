<?php
/**
 * @var App\View\AppView $this
 * @var App\Model\Enum\ContentType $contentType
 * @var App\Model\Entity\ContentTypeFieldSchema $schema
 * @var list<App\Model\Enum\CollectionFieldType> $fieldTypes
 * @var array<string, int> $fieldsInUse
 */
$typeKey = strtolower($contentType->value);
$listUrl = $workspacePath . '/admin/' . $typeKey;
$action = $workspacePath . '/admin/content-model/' . $typeKey . '/fields';
$this->assign('title', $contentType->label() . ' fields');
?>
<?php echo $this->element('admin/breadcrumb', ['crumbs' => [
    ['label' => 'Home', 'url' => $workspacePath . '/admin'],
    ['label' => $contentType->label(), 'url' => $listUrl],
    ['label' => 'Fields', 'url' => null],
]]); ?>

<header class="cms-page-header">
    <div>
        <h1 class="cms-page-header__title"><?php echo h($contentType->label()); ?> fields</h1>
        <div class="cms-page-header__meta">Custom fields every <?php echo h($contentType->label()); ?> record holds alongside its built-in content.</div>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<?php echo $this->Form->create(null, ['url' => $action, 'id' => 'content-fields-form', 'class' => 'cms-schema-form', 'data-schema-form' => true]); ?>

    <div class="cms-editor-layout">
        <div class="cms-editor-layout__main">
            <div class="cms-card cms-card--flush">
                <?php echo $this->element('admin/field-schema/builder', [
                    'schema' => $schema->field_schema ?? [],
                    'fieldTypes' => $fieldTypes,
                    'fieldsInUse' => $fieldsInUse,
                    'errorField' => $schema->getError('field_schema'),
                ]); ?>
            </div>
        </div>
    </div>

    <input type="hidden" name="field_schema_json" data-schema-input value="">
<?php echo $this->Form->end(); ?>

<footer class="cms-savebar">
    <div class="cms-savebar__hint"></div>
    <div class="cms-savebar__actions">
        <a class="cms-btn" href="<?php echo h($listUrl); ?>">Cancel</a>
        <button type="submit" class="cms-btn cms-btn--primary" form="content-fields-form">Save fields</button>
    </div>
</footer>

<?php $this->append('script'); ?>
<script type="module" defer src="<?php echo $this->Url->assetUrl('/js/admin/schema-builder.mjs'); ?>"></script>
<?php $this->end(); ?>
