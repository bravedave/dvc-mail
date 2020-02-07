<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International Public License.
		http://creativecommons.org/licenses/by/4.0/

	*/
	?>
<form class="form" id="<?= $form = strings::rand() ?>">
	<input type="hidden" name="action" value="send email" />
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

});
</script>
