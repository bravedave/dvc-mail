<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * This work is licensed under a Creative Commons Attribution 4.0 International Public License.
 * 		http://creativecommons.org/licenses/by/4.0/
 *
 * */	?>

<div class="row">
	<div class="col pt-4">
		<ul class="list-unstyled mt-4">
			<li><h6>Index</h6></li>

<?php	if ( dvc\mail\config::$ENABLED) {

			if ( 'ews' == dvc\mail\config::$MODE) {	?>

			<li><a href="<?= strings::url( 'ews/account') ?>">account</a></li>
			<li><a href="<?= strings::url( 'ews/agenda') ?>">agenda</a></li>
			<li><a href="<?= strings::url( 'ews/webmail') ?>">webmail</a></li>

<?php		}
			elseif ( 'imap' == dvc\mail\config::$MODE) {	?>

			<li><a href="<?= strings::url( 'webmail') ?>">webmail</a></li>
			<li><a href="<?= strings::url( 'imap/account') ?>">account</a></li>

<?php		}

		}	?>

<?php		if ( class_exists( 'dvc\ews\config')) {	?>
			<li class="mt-1"><a href="<?= strings::url( 'settings') ?>">settings</a></li>

<?php		}	// if ( class_exists( 'dvc\wepm\controller'))	?>

			<li class="mt-1"><a href="<?= strings::url( 'options') ?>">options</a></li>

		</ul>

	</div>

</div>
