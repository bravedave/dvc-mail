<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/	?>
<form class="form" id="<?= $form = strings::rand() ?>">
	<input type="hidden" name="action" value="send email" />
	<input type="hidden" name="tmpdir" value="" />
	<div class="progress mb-2 d-none">
  		<div class="progress-bar progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>

	</div>

	<div class="form-group row">
		<div class="col">
			<div class="input-group">
				<input name="to" class="form-control" required
					type="email" value="<?= $this->data->to ?>" placeholder="Email Address" />

				<div class="input-group-append">
					<div class="input-group-text">to</div>
					<button type="button" class="btn btn-secondary" cc>cc/bcc</button>

				</div>

			</div>

		</div>

	</div>

	<div class="form-group row d-none" cc-control>
		<div class="col">
			<div class="input-group">
				<input name="cc" class="form-control" type="email" placeholder="cc" />
				<div class="input-group-append">
					<div class="input-group-text">cc</div>

				</div>

			</div>

		</div>

	</div>

	<div class="form-group row d-none" bcc-control>
		<div class="col">
			<div class="input-group">
				<input name="bcc" class="form-control" type="email" placeholder="bcc" />
				<div class="input-group-append">
					<div class="input-group-text">bcc</div>

				</div>

			</div>

		</div>

	</div>

	<div class="form-group row">
		<div class="col">
			<input name="subject" type="text" class="form-control" placeholder="Subject" required
				value="<?= $this->data->subject ?>" />

		</div>

	</div>

	<div class="form-group row">
		<div class="col">
			<textarea name="message" class="form-control" required rows="10"
				placeholder="Message"><?= $this->data->message ?></textarea>

		</div>

	</div>

	<div class="row">
		<div class="col text-right">
			<button type="submit" class="btn btn-outline-primary">send <i class="fa fa-paper-plane-o"></i></button>

		</div>

	</div>

</form>
<script>
$(document).ready( function() {
	let form = $('#<?= $form ?>');

	// $('input[name="to"]', form).val( 'david@brayworth.com.au');
	// $('input[name="subject"]', form).val( 'Hello World');
	// $('textarea[name="message"]', form).val( 'Hello World');

	let modal = form.closest( '.modal');

	$('button[cc]', form).on( 'click', function( e) {
		$('[cc-control],[bcc-control]', form).removeClass( 'd-none');
		$(this).remove();

	});

	form.on( 'submit', function( e) {
		let _form = $(this);

		let _data = _form.serializeFormJSON();

		$('i.fa-paper-plane-o', this).removeClass( 'fa-paper-plane-o').addClass('fa-spinner fa-spin');

		_brayworth_.post({
			url : _brayworth_.url('<?= $this->route ?>'),
			data : _data,

		}).then( function( d) {
			_brayworth_.growl( d);
			modal.modal('hide');

		});

		// console.table( _data);

		// try {
		// } catch (error) {
		// 	console.log( error);

		// }

		return false;

	});

	form.on( 'get-attachments', function( e) {
		let _form = $(this);
		let _data = _form.serializeFormJSON();

		if ( '' == _data.tmpdir) return;

		console.log( _data.tmpdir, '<?= $this->route ?>');

		_brayworth_.post({
			url : _brayworth_.url('<?= $this->route ?>'),
			data : {
				action : 'attachments-get',
				tmpdir : _data.tmpdir

			},

		}).then( function( d) {
			_brayworth_.growl( d);
			console.log( 'attachments', d);

		});

	});

	( ( form) => {
		if ( _brayworth_.browser.isMobileDevice) return;	// chain

		// console.log( form[0]);
		// console.log( $('input[name="tmdir"]', form));

		let isAdvancedUpload = ( () => {
			let div = document.createElement('div');
			return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
		})();

		if ( isAdvancedUpload) {
			form.parent()
			.addClass('has-advanced-upload')
			.on('drag dragstart dragend dragover dragenter dragleave drop', (e) => {
				e.preventDefault(); e.stopPropagation();
			})
			.on('dragover dragenter', function( e) {
				$(this).addClass('is-dragover');
			})
			.on('dragleave dragend drop', function( e) {
				$(this).removeClass('is-dragover');

			})
			.on('drop', function(e) {
				e.preventDefault();
				let droppedFiles = e.originalEvent.dataTransfer.files;
				//~ console.log( droppedFiles);

				if (droppedFiles) {
					//~ console.log( 'droppedFiles');

					let data = new FormData();
					data.append('action', 'attachments-upload');
					data.append('tmpdir', $('input[name="tmpdir"]', form).val());
					$.each( droppedFiles, function(i, file) {
						//~ console.log( file);
						data.append('files-'+i, file);

					});

					let progressBar = $('.progress-bar', form);
					progressBar.css('width','0');
					$('.progress', form).removeClass('d-none');

					_brayworth_.post({
						url: _brayworth_.url('<?= $this->route ?>'),
						data: data,
						dataType: 'json',
						cache: false,
						contentType: false,
						processData: false,
						xhr: function() {
							var xhr = new window.XMLHttpRequest();
							xhr.upload.addEventListener("progress", function (e) {
								if (e.lengthComputable) {
									let pc = parseInt( e.loaded / e.total * 100);
									progressBar
									.css('width', pc + '%')
									.attr( 'aria-valuenow', pc);

								}

							})

							return xhr;

						}

					})
					.done( function( r) {
						_brayworth_.growl( r);
						$('input[name="tmpdir"]', form).val( r.tmpdir);
						form.trigger( 'get-attachments');

					})
					.always( ( r) => {
						setTimeout(() => {
							$('.progress', form).addClass('d-none');

						}, 1000);

					})
					.fail( function( r) { alert('there was an error uploading the attachments'); });

				}

			});

		}	// if (isAdvancedUpload && !_me.attachmentContainer.hasClass('has-advanced-upload'))

	})( form);

});
</script>
