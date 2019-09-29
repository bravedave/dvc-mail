<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * This work is licensed under a Creative Commons Attribution 4.0 International Public License.
 * 		http://creativecommons.org/licenses/by/4.0/
 *
 * */

$account = $this->data->account;
// sys::dump( $account);
?>
<form method="post" action="<?= strings::url( $this->route ) ?>" autocomplete="off">
	<input type="hidden" name="action" value="save-account" />

	<div class="form-group row">
		<div class="col">
			<label for="<?= $uid = strings::rand() ?>">server:</label>
			<input type="text" class="form-control" name="server"
				id="<?= $uid ?>"
				required value="<?= $account->server ?>" />

		</div>

	</div>

	<div class="form-group row">
		<div class="col">
			<label for="<?= $uid = strings::rand() ?>">username:</label>
			<input type="text" class="form-control" name="username"
				autocomplete="off"
				id="<?= $uid ?>"
				required value="<?= $account->username ?>" />

		</div>

	</div>

	<div class="form-group row">
		<div class="col">
			<label for="<?= $uid = strings::rand() ?>">password:</label>
			<div class="input-group">
				<input type="password" class="form-control" name="password" value="--------"
					autocomplete="off"
					id="<?= $uid ?>" />
				<div class="input-group-append" id="<?= $uid ?>-control">
					<div class="input-group-text">
						<i class="fa fa-eye"></i>

					</div>

				</div>

			</div>

			<script>
			$(document).ready( function() {
				$('#<?= $uid ?>-control').on( 'click', function( e) {
					let _me = $(this);
					let fld = $('#<?= $uid ?>');

					if ( 'text' == fld.attr( 'type')) {
						fld.attr( 'type', 'password');
						$('.fa-eye-slash', _me).removeClass('fa-eye-slash').addClass('fa-eye');

					}
					else {
						fld.attr( 'type', 'text');
						$('.fa-eye', _me).removeClass('fa-eye').addClass('fa-eye-slash');

					}

				})

			});
			</script>

		</div>

	</div>

	<div class="row">
		<div class="col text-right">
			<button class="btn btn-primary">update account</button>

		</div>

	</div>

</form>