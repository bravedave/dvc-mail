<?php
/**
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * This work is licensed under a Creative Commons Attribution 4.0 International Public License.
 *      http://creativecommons.org/licenses/by/4.0/
 *
** */   ?>

<div class="row">
    <div class="offset-md-3 col">
        <div class="form-check">
			<input type="checkbox" class="form-check-input" id="-email-autoloadnext-"
                data-option="email_autoloadnext"
                value="yes"
				<?php if ( currentUser::option('email_autoloadnext') == 'yes') print 'checked' ?> />

			<label class="form-check-label" for="-email-autoloadnext-">
				Autoload Next Email

			</label>

        </div>

    </div>

</div>
<script>
$(document).ready( function() {
	$.each([
		'#-email-autoloadnext-',
	], function( i, el) {
		$(el).on( 'change', function() {
			let _me = $(this);
			let val = _me.prop( 'checked') ? _me.val() : '';

			//~ console.log( _me.data('option'), val);

            _brayworth_.post({
                url : _brayworth_.url('/'),
                data : {
                    action : 'set-option',
                    key : _me.data('option'),
                    val : val,

                },

            }).then( function( d) {
                _brayworth_.growl( d);

            });

		});

    });

});

</script>