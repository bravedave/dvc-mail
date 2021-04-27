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

			<li><a href="<?= strings::url( 'webmail') ?>">mail</a></li>
			<li><a href="<?= strings::url( 'imap/account') ?>">account</a></li>
			<?php
				if ( method_exists( $this, 'compose')) {	?>
			<li><a href="#" id="<?= $uid = strings::rand() ?>">compose</a></li>
			<script>
      ( _ => $(document).ready( () => {
        // headerClass : '',
        // beforeOpen : function() {},
        // onClose : function() {},
        // onSuccess : function() {},
        $('#<?= $uid ?>').on( 'click', function( e) {
          e.stopPropagation();e.preventDefault();

          let url = _.url('<?= $this->route ?>/compose');
          // console.log( url);

          _.loadModal({
            url : url

          });

        });

      }))( _brayworth_);
			</script>
			<?php
				}	// if ( method_exists( $this, 'compose'))	?>

<?php
				if ($profiles = dvc\imap\account::profiles()) {
					print '<ul class="list-unstyled my-4 pl-2">';
					print '<li><h6>profiles</h6></li>';
					foreach ($profiles as $profile) {
						printf( '<li href="#" data-role="load-profile" data-profile="%s">%s</li>',
							htmlspecialchars( $profile->profile),
							$profile->profile);

					}

					print '</ul>';

				}

				// sys::dump( $profiles, null, false);

			}

		}	?>

<?php		if ( class_exists( 'dvc\ews\config')) {	?>
			<li class="mt-1"><a href="<?= strings::url( 'settings') ?>">settings</a></li>

<?php		}	// if ( class_exists( 'dvc\wepm\controller'))	?>

			<li class="mt-1"><a href="<?= strings::url( 'options') ?>">options</a></li>

			<li class="mt-4">
				<h6 class="my-0">Tests</h6>
				<ul class="list-unstyled">
					<li><a href="<?= strings::url( 'tests/messages') ?>">dump messages</a></li>
					<li><a href="<?= strings::url( 'tests/info') ?>">phpinfo</a></li>
					<li><a href="<?= strings::url( 'tests/encodings') ?>">encodings</a></li>

				</ul>

			</li>

			<li><a href="<?= strings::url( 'changes') ?>">changes</a></li>

		</ul>

	</div>

</div>
<script>
( _ => $(document).ready( () => {
  $('li[data-role="load-profile"]').each( function( i, el) {

    $(el).on( 'contextmenu', function( e) {
      if ( e.shiftKey)
        return;

      e.stopPropagation();e.preventDefault();

      _.hideContexts();

      let _el = $(this);
      let _data = _el.data();
      // console.table( _data);

      let _context = _.context();

      _context.append( $('<a href="#">load profile</a>').on( 'click', function( e) {
        e.stopPropagation();e.preventDefault();

        _.post({
          url : _.url('imap'),
          data : {
            action : 'load-profile',
            profile : _data.profile,

          },

        }).then( function( d) {
          _.growl( d);
          if ( 'ack' == d.response) {
            window.location.reload();

          }

        });

        _context.close();

      }));

      _context.append( $('<a href="#"><i class="bi bi-trash"></i>delete profile</a>').on( 'click', function( e) {
        e.stopPropagation();e.preventDefault();

        _.post({
          url : _.url('imap'),
          data : {
            action : 'delete-profile',
            profile : _data.profile,

          },

        }).then( function( d) {
          _.growl( d);
          if ( 'ack' == d.response) {
            _el.remove();

          }

        });

        _context.close();

      }));

      _context.open( e);

    });;

  });

}))( _brayworth_);
</script>
