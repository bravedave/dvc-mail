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

		<div class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
      id="<?= $_modal = strings::rand() ?>" aria-labelledby="<?= $_modal ?>Label" aria-hidden="true">

			<div class="modal-dialog modal-xl modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header bg-secondary text-white py-2">
						<h5 class="modal-title" id="<?= $_modal ?>Label"><?= $this->title ?></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>

					<div class="modal-body pt-2 px-3 pb-0">
						<div class="form-group row mb-1">
							<div class="col">
								<div class="input-group">
									<input name="to" class="form-control" required
										type="email" value="<?= $this->data->to ?>" placeholder="Email Address" />

									<div class="input-group-append">
										<div class="input-group-text">to</div>
										<button type="button" class="btn btn-secondary" tabindex="-1" cc>cc/bcc</button>

									</div>

								</div>

							</div>

						</div>

						<div class="form-group row mb-1 d-none" cc-control>
							<div class="col">
								<div class="input-group">
									<input name="cc" class="form-control" type="email" placeholder="cc" />
									<div class="input-group-append">
										<div class="input-group-text">cc</div>

									</div>

								</div>

							</div>

						</div>

						<div class="form-group row mb-1 d-none" bcc-control>
							<div class="col">
								<div class="input-group">
									<input name="bcc" class="form-control" type="email" placeholder="bcc" />
									<div class="input-group-append">
										<div class="input-group-text">bcc</div>

									</div>

								</div>

							</div>

						</div>

						<div class="form-group row mb-1"><!-- subject -->
							<div class="col">
								<input name="subject" type="text" class="form-control" placeholder="Subject" required
									value="<?= $this->data->subject ?>" />

							</div>

						</div>

						<div class="form-group row mb-2"><!-- message -->
							<div class="col">
								<textarea name="message" class="form-control" required rows="12"
                  id="<?= $_messageID = strings::rand() ?>"
									placeholder="Message"><?= $this->data->message ?></textarea>

							</div>

						</div>

					</div>

					<div class="modal-footer">
            <div class="flex-fill" upload>
              <div class="progress mb-2 d-none">
                <div class="progress-bar progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>

              </div>

              <div id="<?= $_attachments = strings::rand() ?>">&nbsp;</div>

            </div>

						<button type="submit" class="btn btn-outline-primary">send <i class="bi bi-cursor"></i></button>

					</div>

				</div>

			</div>

		</div>

	</form>
	<script>
	$(document).ready( () => {

		( _ => {

      $('#<?= $_modal ?>')
      .on( 'init-tinymce', e => {
        // inline: true,
        let options = {
          browser_spellcheck : true,
          font_formats: "Andale Mono=andale mono,times;"+
            "Arial=arial,helvetica,sans-serif;"+
            "Arial Black=arial black,avant garde;"+
            "Century Gothic=century gothic,arial,helvetica,sans-serif;"+
            "Comic Sans MS=comic sans ms,sans-serif;"+
            "Courier New=courier new,courier;"+
            "Helvetica=helvetica;"+
            "Impact=impact,chicago;"+
            "Symbol=symbol;"+
            "Tahoma=tahoma,arial,helvetica,sans-serif;"+
            "Terminal=terminal,monaco;"+
            "Times New Roman=times new roman,times;"+
            "Trebuchet MS=trebuchet ms,geneva;"+
            "Verdana=verdana,geneva;"+
            "Webdings=webdings;"+
            "Wingdings=wingdings,zapf dingbats",
          branding: false,
          document_base_url : _.url('',true),
          menubar : false,
          selector: '#<?= $_messageID ?>',
          paste_data_images: true,
          relative_urls : false,
          remove_script_host : false,
          setup : function(ed) {
            ed.on( 'keydown', e => {
              if (e.keyCode == 9) { // tab pressed
                if (e.shiftKey)
                  ed.execCommand('Outdent');
                else
                  ed.execCommand('Indent');

                e.preventDefault();
                return false;

              }

            });

            ed.on( 'blur', e => tinyMCE.triggerSave())

          }

        };

        options = _.extend( options, {
          selector: '#<?= $_messageID ?>',
          plugins: [
            'paste',
            'imagetools',
            'table',
            'autolink',
            'lists',
            'link',

          ],
          statusbar : false,
          toolbar: 'undo redo | bold italic | bullist numlist outdent indent blockquote table link mybutton | styleselect fontselect fontsizeselect | forecolor backcolor',
          contextmenu: 'paste | inserttable | cell row column deletetable',

        });

        tinymce.init(options);

      })
      .on( 'shown.bs.modal', e => _.tiny().then( () => $('#<?= $_modal ?>').trigger('init-tinymce')))
      .on( 'hide.bs.modal', e => $('#<?= $_form ?>').trigger('cleanup'));

      $('button[cc]', '#<?= $_form ?>').on( 'click', function( e) {
        $('[cc-control],[bcc-control]', '#<?= $_form ?>').removeClass( 'd-none');
        $(this).remove();

      });

      $('#<?= $_form ?>')
      .on( 'cleanup', function(e) {
        let _form = $(this);
        let _data = _form.data();
        let _form_data = _form.serializeFormJSON();

        if ( 'sending' != _data.status) {
          if ( '' != _form_data.tmpdir) {

            _.post({
              url : _.url('<?= $this->route ?>'),
              data : {
                action : 'cleanup-temp',
                tmpdir : _form_data.tmpdir

              },

            })
            .then( d => _.growl( d));

          }

        }

      })
      .on( 'get-attachments', function( e) {
        let _form = $(this);
        let _data = _form.serializeFormJSON();

        if ( '' == _data.tmpdir) return;

        console.log( _data.tmpdir, '<?= $this->route ?>');
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

      })
      .on( 'submit', function( e) {
        let _form = $(this);

        let _data = _form.serializeFormJSON();
        console.log( _data);

        $('i.bi-cursor', this)
        .removeClass( 'bi-cursor')
        .addClass('spinner-grow spinner-grow-sm');

        _form.data( 'status', 'sending');

        _.post({
          url : _.url('<?= $this->route ?>'),
          data : _data,

        }).then( function( d) {
          _.growl( d);

          _form.data( 'status', 'sent');

          $('#<?= $_modal ?>')
          .trigger('success')
          .modal( 'hide');

        });

        return false;

      });

			if ( _.browser.isMobileDevice) return;

			let isAdvancedUpload = ( () => {
				let div = document.createElement('div');
				return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
			})();

			if ( isAdvancedUpload) {

				$('.modal-footer > [upload]', '#<?= $_modal ?>')
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
