<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * This work is licensed under a Creative Commons Attribution 4.0 International Public License.
 *      http://creativecommons.org/licenses/by/4.0/
 *
*/	?>
<nav class="navbar navbar-light bg-light sticky-top" role="navigation" >
	<ul class="navbar-nav navbar-expand mr-auto d-none d-sm-flex">
		<li class="nav-item d-none" id="<?= $uid = strings::rand() ?>">
			<a id="<?= $uidFolderHide = strings::rand() ?>"
				class="btn btn-outline-dark rounded-circle"
				href="#">
				<i class="fa fa-bars"></i>

			</a>

		</li>
		<script>
		$(document).on('mail-load-complete', function( e) {
			$('#<?= $uid ?>').removeClass('d-none');

		});
		$(document).ready( function() {
			$('#<?= $uidFolderHide ?>').on( 'click', function( e) {
				e.stopPropagation(); e.preventDefault();

				$(document).trigger( 'mail-toggle-view');

			});

		});
		</script>

		<li class="nav-item">
			<a id="<?= $uid = strings::rand() ?>"
				class="btn btn-light"
				href="#">
				<i class="fa fa-envelope"></i>

			</a>

		</li>
		<script>
		$(document).ready( function() {
			// headerClass : '',
			// beforeOpen : function() {},
			// onClose : function() {},
			// onSuccess : function() {},
			$('#<?= $uid ?>').on( 'click', function( e) {
				e.stopPropagation();e.preventDefault();

				let url = _brayworth_.url('home/compose');
				// console.log( url);

				_brayworth_.loadModal({
					url : url

				});

			});

		});
		</script>

	</ul>

	<?php printf( '<a href="%s" class="navbar-brand" >%s</a>', \url::$URL, $this->data->title);	?>

</nav>
