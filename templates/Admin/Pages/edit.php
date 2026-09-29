<?php
/**
 * @var Cake\View\View $this
 * @var App\Model\Entity\Page $page
 * @var App\Model\Entity\ContentTypeFieldSchema $fieldSchema
 * @var array<string, string> $dataErrors
 */
$this->assign('title', $page->title);
?>
<?php echo $this->element('admin/breadcrumb', [
    'crumbs' => [
        ['label' => 'Home', 'url' => $workspacePath . '/admin'],
        ['label' => 'Pages', 'url' => $workspacePath . '/admin/pages'],
        ['label' => $page->title, 'url' => null],
    ],
]); ?>

<header class="cms-page-header">
    <div>
        <?php echo $this->element('admin/pages/title_field', ['page' => $page]); ?>
        <div class="cms-page-header__meta">
            Last edited by <strong><?php echo h($page->author?->name ?? 'Admin'); ?></strong>
            <?php if ($page->modified !== null) { ?>
                · <time datetime="<?php echo h($page->modified->format(\DATE_ATOM)); ?>"><?php echo h($page->modified->timeAgoInWords()); ?></time>
            <?php } ?>
        </div>
    </div>
    <div class="cms-page-header__actions">
        <a
            class="cms-btn cms-btn--ghost"
            href="<?php echo h($workspacePath); ?>/<?php echo h($page->slug); ?>"
            target="_blank"
            rel="noopener"
        >Preview</a>
        <button type="submit" class="cms-btn" form="page-savedraft-form">Save Draft</button>
        <button type="submit" class="cms-btn cms-btn--primary" form="page-publish-form">Publish</button>
    </div>
</header>

<?php echo $this->Flash->render(); ?>

<?php echo $this->element('admin/pages/form', ['page' => $page, 'fieldSchema' => $fieldSchema, 'dataErrors' => $dataErrors ?? [], 'submitLabel' => 'Save']); ?>
