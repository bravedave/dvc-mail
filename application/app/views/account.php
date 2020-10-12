<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

$account = $this->data->account;
// sys::dump( $account);
?>
<form id="<?= $_form = strings::rand() ?>" method="POST" action="<?= strings::url( $this->route ) ?>" autocomplete="off">
	<input type="hidden" name="action" value="save-account" />

	<div class="form-group row">
		<div class="col">
			<label for="<?= $uid = strings::rand() ?>">server:</label>
			<input type="text" class="form-control" name="server"
				id="<?= $uid ?>"
				required value="<?= $account->server ?>" />

		</div>

	</div>

<?php	if ( 'imap' == dvc\mail\config::$MODE) {	?>
	<div class="form-group row">
		<div class="col">
			<label for="<?= $uid = strings::rand() ?>">server type:</label>
			<select class="form-control" name="type" id="<?= $uid ?>">
				<option value="">linux</option>
				<option value="exchange" <?php if ( 'exchange' == $account->type) print 'selected'  ?>>exchange</option>

			</select>

		</div>

	</div>

<?php	}	// if ( 'imap' == dvc\mail\config::$MODE)	?>

	<div class="form-group row">
		<div class="col">
			<label for="<?= $uid = strings::rand() ?>">name:</label>
			<input type="text" class="form-control" name="name"
				autocomplete="off"
				id="<?= $uid ?>"
				required value="<?= $account->name ?>" />

		</div>

	</div>

	<div class="form-group row">
		<div class="col">
			<label for="<?= $uid = strings::rand() ?>">email:</label>
			<input type="email" class="form-control" name="email"
				autocomplete="off"
				id="<?= $uid ?>"
				required value="<?= $account->email ?>" />

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
				<input class="form-control" name="password" autocomplete="new-password" id="<?= $uid ?>" />
				<div class="input-group-append" id="<?= $uid ?>-control">
					<div class="input-group-text">
						<i class="fa fa-eye"></i>

					</div>

				</div>

				<div class="input-group-append">
					<button type="button" class="btn input-group-text" disabled id="<?= $uid ?>-verify">
						verify

					</button>

				</div>

				<script>
				$(document).ready( function() {
					$('#<?= $uid ?>')
					.attr('type','password')
					.val('--------')
          .on( 'keyup', function(e) {
            let _me = $(this);
            if ( '' == _me.val()) {
              $('#<?= $uid ?>-verify').prop( 'disabled', true);

            }
            else {
              $('#<?= $uid ?>-verify').prop( 'disabled', false);

            }

          });

				});
				</script>

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

				});

				$('#<?= $uid ?>-verify').on( 'click', function( e) {
					let _me = $(this);
					let fld = $('#<?= $uid ?>');
          let _form = $('#<?= $_form ?>');
          let _data = _form.serializeFormJSON();

          // console.log( _data);
          let pkt = {
            action : 'verify',
            email_type : _data.type,
            email_server : _data.server,
            email_account : _data.username,
            email_password : _data.password,

          };

          // console.log( pkt);
          // return;

          ( _ => {
            _.post({
              url : _.url('<?= $this->route ?>'),
              data : pkt,

            }).then( d => {
              if ( 'ack' == d.response) {
                _me.parent().append('<div class="input-group-text"><i class="fa fa-check text-success"></i></div>');
                _me.remove();

              }
              else {
                _.growl( d);

              }

            });

          }) (_brayworth_);

				});

			});
			</script>

		</div>

	</div>

	<div class="form-group row">
		<div class="col-md-9">
			<label for="<?= $uid = strings::rand() ?>">smtp server:</label>
			<input type="text" class="form-control" name="smtp_server" placeholder="smtp server"
				autocomplete="off"
				id="<?= $uid ?>"
				value="<?= $account->smtp_server ?>" />

		</div>

		<div class="col-md-3">
			<label for="<?= $uid = strings::rand() ?>">smtp port:</label>
			<input type="text" class="form-control" name="smtp_port" placeholder="465,587"
				autocomplete="off"
				id="<?= $uid ?>"
				value="<?= $account->smtp_port ?>" />

		</div>

	</div>

	<div class="form-group row">
		<div class="col">
			<label for="<?= $uid = strings::rand() ?>">smtp username:</label>
			<input type="text" class="form-control" name="smtp_username" placeholder="smtp username"
				autocomplete="off"
				id="<?= $uid ?>"
				value="<?= $account->smtp_username ?>" />

		</div>

	</div>

	<div class="form-group row">
		<div class="col">
			<label for="<?= $uid = strings::rand() ?>">smtp password:</label>
			<div class="input-group">
				<input class="form-control" name="smtp_password" placeholder="smtp password" autocomplete="new-password" id="<?= $uid ?>" />
				<div class="input-group-append" id="<?= $uid ?>-control">
					<div class="input-group-text">
						<i class="fa fa-eye"></i>

					</div>

				</div>

				<script>
				$(document).ready( function() {
					$('#<?= $uid ?>')
					.attr('type','password')
					.val('--------');

				});
				</script>

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

				});

			});
			</script>

		</div>

	</div>

	<div class="form-group row">
		<div class="col">
			<label for="<?= $uid = strings::rand() ?>">profile:</label>
			<input type="text" class="form-control" name="profile" placeholder="default profile"
				id="<?= $uid ?>"
				value="<?= $account->profile ?>" />

		</div>

	</div>

	<div class="row">
		<div class="col text-right">
			<button class="btn btn-primary">update account</button>

		</div>

	</div>

</form>
