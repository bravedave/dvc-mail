<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/	?>

<div id="<?= $_wrap = strings::rand() ?>">
	<form id="<?= $_form = strings::rand() ?>" autocomplete="off">
		<input type="hidden" name="action" value="send email" />
		<input type="hidden" name="tmpdir" value="" />

		<div class="modal fade" tabindex="-1" role="dialog" id="<?= $_modal = strings::rand() ?>" aria-labelledby="<?= $_modal ?>Label" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header bg-secondary text-white py-2">
						<h5 class="modal-title" id="<?= $_modal ?>Label"><?= $this->title ?></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>

					<div class="modal-body">
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

						<div class="form-group row"><!-- subject -->
							<div class="col">
								<input name="subject" type="text" class="form-control" placeholder="Subject" required
									value="<?= $this->data->subject ?>" />

							</div>

						</div>

						<div class="form-group row"><!-- message -->
							<div class="col">
								<textarea name="message" class="form-control" required rows="10"
									placeholder="Message"><?= $this->data->message ?></textarea>

							</div>

						</div>

					</div>

					<div class="modal-footer">
						<div class="mr-auto" id="<?= $_attachments = strings::rand() ?>"></div>
						<button type="submit" class="btn btn-outline-primary">send <i class="fa fa-paper-plane-o"></i></button>

					</div>

				</div>
			</div>
		</div>
	</form>
	<script>
	$(document).ready( () => {

		$('#<?= $_modal ?>').on( 'hidden.bs.modal', e => { $('#<?= $_wrap ?>').remove(); });
		$('#<?= $_modal ?>').modal( 'show');

		$('button[cc]', '#<?= $_form ?>').on( 'click', function( e) {
			$('[cc-control],[bcc-control]', _form).removeClass( 'd-none');
			$(this).remove();

		});

		$('#<?= $_form ?>')
		.on( 'get-attachments', function( e) {
			let _form = $(this);
			let _data = _form.serializeFormJSON();

			if ( '' == _data.tmpdir) return;

			console.log( _data.tmpdir, '<?= $this->route ?>');

			( _ => {
				_.post({
					url : _.url('<?= $this->route ?>'),
					data : {
						action : 'attachments-get',
						tmpdir : _data.tmpdir

					},

				}).then( function( d) {
					if ( 'ack' == d.response) {
						console.log( d);
						let ul = $('<ul class="list-inline"></ul>');
						$('#<?= $_attachments ?>').html('').append( ul);
						$.each( d.attachments, ( i, file) => {
							console.log( 'attachment', file);
							$('<li class="list-inline-item"></li>').html( file.name + '(' + file.size + ')').appendTo( ul);

						});

					}
					else {
						_.growl( d);

					}

				});

			}) (_brayworth_);

		})
		.on( 'submit', function( e) {
			let _form = $(this);
			let _data = _form.serializeFormJSON();

			$('i.fa-paper-plane-o', this)
			.removeClass( 'fa-paper-plane-o')
			.addClass('fa-spinner fa-spin');

			( _ => {
				_.post({
					url : _.url('<?= $this->route ?>'),
					data : _data,

				}).then( function( d) {
					_.growl( d);

					$('#<?= $_modal ?>')
					.trigger('success')
					.modal( 'hide');

				});

			}) (_brayworth_);

			// console.table( _data);

			return false;
		});

		( _ => {

			if ( _.browser.isMobileDevice) return;

			let isAdvancedUpload = ( () => {
				let div = document.createElement('div');
				return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
			})();

			if ( isAdvancedUpload) {

				let _modalBody = $('.modal-body', '#<?= $_form ?>');

				_modalBody
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
				.on('drop', e => {
					e.preventDefault();
					let droppedFiles = e.originalEvent.dataTransfer.files;
					// console.log( droppedFiles);

					if (droppedFiles) {
						// console.log( 'droppedFiles');

						let data = new FormData();
						data.append('action', 'attachments-upload');
						data.append('tmpdir', $('input[name="tmpdir"]', '#<?= $_form ?>').val());
						$.each( droppedFiles, function(i, file) {
							// console.log( file);
							data.append('files-'+i, file);

						});

						let progressBar = $('.progress-bar', '#<?= $_form ?>');
						progressBar.css('width','0');
						$('.progress', '#<?= $_form ?>').removeClass('d-none');

						_.post({
							url: _.url('<?= $this->route ?>'),
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
						.then( r => {
							_.growl( r);
							$('input[name="tmpdir"]', '#<?= $_form ?>').val( r.tmpdir);
							$('#<?= $_form ?>').trigger( 'get-attachments');

							setTimeout( () => $('.progress', '#<?= $_form ?>').addClass('d-none'), 1000);

						})
						.fail( r => alert('there was an error uploading the attachments'));

					}

				});

			}	// if (isAdvancedUpload && !_me.attachmentContainer.hasClass('has-advanced-upload'))

		}) (_brayworth_);

	});
	</script>

</div>
