<?= view('boomcms::header', ['title' => $page->getTitle()]) ?>

<div id="b-topbar" class='b-page-toolbar b-toolbar b-toolbar-vertical'>
	<?= $menuButton() ?>
	<?= $menu() ?>

	<div id="b-topbar-page-buttons">
		<?php if (Gate::allows('add', $page)): ?>
			<?= $button('plus', 'toolbar.add', ['id' => 'b-page-addpage']) ?>
		<?php endif ?>

		<?php if (Gate::allows('edit', $page)): ?>
			<div id="b-page-settings-menu">
				<?= $button('cog', 'toolbar.settings', ['id' => 'b-page-settings']) ?>
			</div>

            <button id="b-page-version-status" class="b-button" data-status="<?= $page->getCurrentVersion()->status() ?>">
                <?= $page->getCurrentVersion()->status() ?>
            </button>
		<?php endif ?>

		<?php if (Gate::allows('publish', $page)): ?>
			<?= $button('eye', 'toolbar.visible', ['id' => 'b-page-visible', 'class' => $page->isVisible() ? 'b-page-visibility ' : 'b-page-visibility ui-helper-hidden']) ?>
			<?= $button('eye-slash', 'toolbar.invisible', ['id' => 'b-page-invisible', 'class' => $page->isVisible() ? 'b-page-visibility ui-helper-hidden' : 'b-page-visibility']) ?>
		<?php endif ?>

		<?php if (Gate::allows('delete', $page)) : ?>
            <?php if ($page->canBeDeleted()): ?>
    			<?= $button('trash-o', 'toolbar.delete', ['id' => 'b-page-delete']) ?>
            <?php else: ?>
                <?= $button('trash-o', 'toolbar.nodelete', ['id' => 'b-page-delete', 'disabled' => 'disabled']) ?>
            <?php endif ?>
		<?php endif ?>

		<div class="b-page-container">
			<?= view('boomcms::editor.toolbar.view-live-button') ?>
		</div>

        <div class="b-page-container">
            <?= $button('question', 'toolbar.help', [
                'id'         => 'b-gethelp',
                'data-email' => Settings::get('site.support.email'),
            ]) ?>
        </div>
	</div>

    <div id="wysihtml5-toolbar" class="b-toolbar b-toolbar-vertical b-toolbar-text">
        <?php foreach (BoomCMS\UI\TextEditorToolbar::getAvailableButtonSets() as $set): ?>
            <?= new BoomCMS\UI\TextEditorToolbar($set) ?>
        <?php endforeach ?>
    </div>
</div>

<div id="b-page-settings-toolbar">
    <?= view('boomcms::editor.page.settings.index') ?>
</div>

<?= view('boomcms::editor.toolbar.footer') ?>
