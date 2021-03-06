<div class="b-assets-upload">
	<form method="post" enctype="multipart/form-data" class="b-assets-upload-form">
		<div class="b-assets-upload-container">
            <div class="b-assets-upload-info">
                <p>
                    Drag and drop files here, or
                    <label for="b-assets-upload-file">select files</label>
                    to start uploading.
                </p>

                <p class="message"></p>

                <div class="b-assets-upload-progress"></div>
                <?= $button('times', 'cancel', ['class' => 'b-assets-upload-cancel']) ?>
            </div>

			<input type="file" name="b-assets-upload-files[]" id="b-assets-upload-file" multiple min="1" max="5" />
            <?= $button('times', 'close-upload', ['class' => 'b-assets-upload-close b-button-withtext']) ?>
        </div>
	</form>
</div>
