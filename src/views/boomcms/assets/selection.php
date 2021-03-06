<div class="b-assets-view">
    <div class="b-settings">
        <div class="b-settings-menu">
            <ul>
                <li class="b-settings-close">
                    <a href="#">
                        <span class="fa fa-close"></span>
                        <?= trans('boomcms::asset.close') ?>
                    </a>
                </li>

                <li>
                    <a href="#b-selection-tags"<% if (section === 'tags') { %> class="selected"<% } %> data-section='tags'>
                        <span class="fa fa-tags"></span>
                        <?= trans('boomcms::asset.tags') ?>
                    </a>
                </li>

                <li class='group'>
                    <a href="#b-selection-download" data-section="download">
                        <span class="fa fa-download"></span>
                        <?= trans('boomcms::asset.selection.download.heading') ?>
                    </a>
                </li>

                <li class="b-setting-delete">
                    <a href="#b-selection-delete"<% if (section === 'delete') { %> class="selected"<% } %> data-section='delete'>
                        <span class="fa fa-trash-o"></span>
                        <?= trans('boomcms::asset.selection.delete.heading') ?>
                    </a>
                </li>
            </ul>

            <a href="#" class="toggle">
                <span class="fa fa-caret-right"></span>
                <span class="fa fa-caret-left"></span>
                <span class="text">Toggle menu</span>
            </a>
        </div>

        <div class="b-settings-content">
            <div id="b-selection-tags"<% if (section === 'tags') { %> class="selected"<% } %>>
                <?= view('boomcms::assets.tags') ?>
            </div>

            <div id="b-selection-download"<% if (section === 'download') { %> class="selected"<% } %>>
                <h1><?= trans('boomcms::asset.selection.download.heading') ?></h1>

                <form id="b-assets-download-filename">
                    <label>
                        <p><?= trans('boomcms::asset.selection.download.filename') ?></p>
                        <input type="text" name="filename" value="<?= trans('boomcms::asset.selection.download.default') ?>" />
                    </label>

                    <?= $button('download', 'download', [
                        'type'  => 'submit',
                        'class' => 'b-button-withtext',
                    ]) ?>
                </form>
            </div>

            <div id="b-selection-delete"<% if (section === 'delete') { %> class="selected"<% } %>>
                <h1><?= trans('boomcms::asset.selection.delete.heading') ?></h1>
                <p><?= trans('boomcms::asset.selection.delete.confirm') ?></p>

                <ul>
                    <% for (var i = 0; i < selection.models.length; i++) { %>
                        <li><%= selection.models[i].getTitle() %></li>
                    <% } %>
                </ul>

                <?= $button('trash-o', 'delete-assets', [
                    'class' => 'b-button-withtext b-assets-delete',
                ]) ?>
            </div>
        </div>
    </div>
</div>

<?= view('boomcms::tags.tag') ?>
