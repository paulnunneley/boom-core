<div id="b-assets-manager">
	<div id="b-topbar" class="b-asset-manager">
		<?= Menu::factory('boom')->sort('priority')  ?>

		<div id="b-page-actions">
			<span id="boom-assets-upload-menu">
				<button id="b-assets-upload" class="boom-button ui-button-text-icon" data-icon="ui-icon-boom-upload">
					<?=__('Upload files')?>
				</button>
			</span>
		</div>

		<div id="b-assets-buttons">
			<button id="b-button-multiaction-edit" disabled="disabled" class="boom-button ui-button-text-icon" data-icon="ui-icon-boom-edit">
				<?=__('View')?>/<?=__('Edit')?>
			</button>
			<button id="b-button-multiaction-delete" disabled="disabled" class="boom-button ui-button-text-icon" data-icon="ui-icon-boom-delete">
				<?=__('Delete')?>
			</button>
			<button id="b-button-multiaction-download" disabled="disabled" class="boom-button ui-button-text-icon" data-icon="ui-icon-boom-download">
				<?=__('Download')?>
			</button>
			<button id="b-button-multiaction-tag" disabled="disabled" class="boom-button ui-button-text-icon" data-icon="ui-icon-boom-tag">
				<?=__('Add Tags')?>
			</button>
			<button id="b-button-multiaction-clear" disabled="disabled" class="boom-button ui-button-text-icon" data-icon="ui-icon-boom-cancel">
				<?=__('Clear Selection')?>
			</button>
		</div>

		<span id="boom-page-user-menu">
			<button id="b-page-user" class="boom-button" data-icon="ui-icon-boom-person">
				<?=__('Profile')?>
			</button>
		</span>
		<div id="b-assets-pagination"></div>
		<div id="b-assets-stats"></div>
	</div>

	<div id="b-assets-filters">
		<span>
			<button id="b-assets-all" class="boom-button">
				<?=__('All assets')?>
			</button>
		</span>

		<input type='text' class="b-filter-input" id="b-assets-filter-title" placeholder="Search by asset name" />

		<?= Form::select('types', array_merge(array('' => 'Filter by type'), ORM::factory('Asset')->types()), NULL, array('id' => 'b-assets-types')) ?>

		<div id='b-tags-search'>
			<input type='text' class="b-filter-input" placeholder="Type a tag name" />
			<ul class="b-tags-list">
			</ul>
		</div>
	</div>

	<div id="b-assets-content"></div>
</div>