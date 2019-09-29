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

namespace dvc\imap;
use strings;    ?>

<div class="row">
	<div class="col pt-4">
		<ul class="list-unstyled mt-4">
			<li><h6>Index</h6></li>

			<li><a href="<?= strings::url( sprintf( '%s/account', $this->route)) ?>">account</a></li>
			<li><a href="<?= strings::url( sprintf( '%s/webmail', $this->route)) ?>">webmail</a></li>

		</ul>

	</div>

</div>
