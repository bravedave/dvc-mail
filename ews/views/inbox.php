<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International Public License.
		http://creativecommons.org/licenses/by/4.0/
	*/	?>
<form id="<?= $uidFrm = strings::rand() ?>">
	<input type="hidden" name="user" value="0" />
	<input type="hidden" name="action" />

</form>

<style>
.<?= $uidCSS_dropHere = strings::rand(); ?> { border: 2px solid #ddd; background-color: #eee }
</style>

<div class="row h-100">
	<div class="d-none d-sm-block col-sm-3 col-md-1 h-100" id="<?= $uidFolders = strings::rand() ?>">folders ...</div>
	<div class="col-sm-9 col-md-3 border border-light h-100" style="overflow-y: auto;" id="<?= $uidMsgs = strings::rand() ?>">messages ...</div>
	<div class="d-none d-md-block col-md-8 h-100" id="<?= $uidViewer = strings::rand() ?>"></div>

</div>
<script>
/**------------------------------------------------ */
let reply = function() {
	let frame = $('iframe', '#<?= $uidViewer ?>');
	if ( frame.length < 1) return;

	let _document = frame[0].contentDocument;
	let _body = $('div[message]', _document);

	let _wrap = $('<div data-role="original-message" style="border-left: 2px solid #eee; padding-left: 5px;"></div>');
		if ( _brayworth_.browser.isMobileDevice) {
			_wrap.text( _body.text().replace(/\n\s*/g,"\n").replace(/\n\n/g,"\n").trim())

		}
		else {
			_wrap.html( _body.clone().html());

		}
		$('p', _wrap).each(function() {
			let _me = $(this);
			if( _me.html().length == 0)
				_me.remove();

		});

	let _subject = $('[data-role="subject"]', _document).text().trim();
	let _to = $('[data-role="from"]', _document).text();
	let _time = $('[data-role="time"]', _document).text();
	if ( '' != String( _time)) {
		if ( '' != String( _to)) {
			_wrap.prepend('on ' + _time + ' ' + _to + ' wrote:');

		}
		else {
			_wrap.prepend('message on ' + _time + ' contained:');

		}

	}

	_wrap.prepend('<br />');

	if ( !/^re: /i.test( _subject))
		_subject = 're: ' + _subject;

	let j = {
		to : _to,
		subject : _subject,
		message : _brayworth_.browser.isMobileDevice ? _wrap.text() : _wrap[0].outerHTML

	};

	_brayworth_.email.activate( j);
	// console.log( _to, _time, _body);

	return;

	var wrap = $('<div />');
	if ( $(this).data('role') == 'reply-fast')
		wrap.append('<p>' + $('#reply-fast-response').val() + '</p>');

	$('[data-role="attachment-control"],  [data-role="tag-control"]', _wrap).each(function() {
		$(this).remove();
	});

	if ( /^reply/.test( $(this).data('role')) && to != undefined) {
		j.to = to;
		j.in_reply_to = container.data('messageid');
		j.in_reply_to_msg = container.data('uid');
		j.in_reply_to_folder = container.data('folder');
		//~ console.log( 'reply', j.in_reply_to_msg = container.data('uid'));

	}
	else if ( /^forward/.test( $(this).data('role'))) {
		j.forward_msg = container.data('uid');
		j.forward_folder = container.data('folder');
		//~ console.log( 'forward', j.forward_msg = container.data('uid'));
		j.callback = function() {
			this.GetAttachmentsFromAnotherMessage( j.forward_msg, j.forward_folder );

		}

	}

	if ( $(this).data('role') == 'reply-all-button') {
		var e, a = [];
		e = container.data('to');
		if ( e != undefined) a.push(e);

		e = container.data('cc');
		if ( e != undefined) a.push(e);

		if ( a.length) j.cc = a.join(',');

	}

}

let MessageDrop = function( e) {	/* drag drop move messages */
	// console.log( 'handle drop');
	e.preventDefault();

	// Get the data, which is the id of the drop target
	let data = e.originalEvent.dataTransfer.getData("text");
	if ( data == '')
		return;

	// console.log( 'MessageDrop', data);
	// return;

	let src = $('#' + data);
	if ( src.length) {
		// let _data = src.data();

		// console.log( 'src', src);
		// console.log( 'data', data);
		// console.log( 'data', $( e.originalEvent.target).data('folder'));
		// console.log( 'data', e.originalEvent.target);
		let _data = $( e.originalEvent.target).data();
		// console.log( 'data', _data);

		$('#' + data).trigger('execute-action', {
			action : 'move-message',
			targetFolder : _data.folder

		});

	} else { console.log( 'cannot find src'); }

	e.originalEvent.dataTransfer.clearData();	// Clear the drag data cache (for all formats/types)

	$(this).trigger( 'dragleave');

}	/* end: drag drop move messages */

/**------------------------------------------------ */


$(document).on( 'mail-folderlist', function( e) {
	let frm = $('#<?= $uidFrm ?>');
	let data = frm.serializeFormJSON();

	let _list = function( folders) {
		let ul = $('<ul class="list-unstyled small" />');

		$.each( folders, function( i, fldr) {
			// console.log( fldr);

			let ctrl;
			$('<li class="pt-1 py-md-0 py-3 pointer" />').appendTo(ul)
			.append( ctrl = $('<div class="text-truncate" />').html( fldr.name));

			ctrl
			.attr('title', fldr.name)
			.data('folder', fldr.fullname)
			.on( 'click', function( e) {
				let _me = $(this);
				let _data = _me.data();

				$(document).trigger( 'mail-messages', _data.folder);

				//~ $('#submit-folder')
				//~ .val( $(this).data('folder'))
				//~ .closest('form').submit();
			})
			.on( 'dragover', function( e) { e.preventDefault(); })
			.on( 'dragenter', function( e) { e.preventDefault(); $( this).addClass('<?= $uidCSS_dropHere ?>'); e.originalEvent.dataTransfer.dropEffect = "copy"; })
			.on( 'dragleave', function( e) { e.preventDefault(); $( this).removeClass('<?= $uidCSS_dropHere ?>'); })
			.on( 'drop', MessageDrop)
			;

			//~ if ( !!el.subFolders) {}

		});

		$('#<?= $uidFolders ?>').html('<div class="row bg-light text-muted"><div class="col"><h6 class="m-0">Folders</h6></div></div>').append( ul);
		//~ console.log( folders);

	}

	let key = '<?= $this->route ?>-lastfolders';
	let lastFolders = sessionStorage.getItem( key);
	// console.log( key, lastFolders);
	if ( !!lastFolders) {
		_list( JSON.parse( lastFolders));
		sessionStorage.removeItem( key);

	}

	data.action = 'get-folders';
	let postData = {
		url : _brayworth_.url('<?= $this->route ?>'),
		data : data,

	};
	// console.log( postData);
	_brayworth_.post( postData).then( function( d) {
		if ( 'ack' == d.response) {
			sessionStorage.setItem( key, JSON.stringify( d.folders));
			_list( d.folders);

		}
		else {
			_brayworth_.growl( d);

		}

	});

});

$(document).on( 'mail-messages-reload', function( e, folder) {
	let key = '<?= $this->route ?>' + folder + '-lastmessages-';
	sessionStorage.removeItem( key);

	$(document).trigger( 'mail-messages', folder);

});

$(document).on( 'mail-messages', function( e, folder) {
	let frm = $('#<?= $uidFrm ?>');
	let data = frm.serializeFormJSON();

	data.action = 'get-messages';
	// console.log( data);
	if ( !!folder) { data.folder = folder; }

	let _list = function( messages) {
		// console.log( messages);
		$('#<?= $uidMsgs ?>').html('<div class="row bg-light text-muted"><div class="col"><h6 class="m-0">'+('undefined' == typeof data.folder ? 'messages' : data.folder)+'</h6></div></div>');

			//~ if ( !!el.subFolders) {}

		$.each( messages, function( i, msg) {

			// console.log( msg);
			let from = $('<div class="col text-truncate" from />').html( msg.from);
			if ( 'no' == msg.seen) from.addClass('font-weight-bold');

			let received = $('<div class="col-3 pl-0 text-right text-truncate small" />');
			let subject = $('<div class="col-9 text-truncate" subject />').html( msg.subject).attr( 'title', msg.subject);

			let time = _brayworth_.moment( msg.received);
			let stime = time.format( 'YYYY-MM-DD') == _brayworth_.moment().format( 'YYYY-MM-DD') ? time.format('LT') : time.format('l')
			// console.log( time.format( 'YYYY-MM-DD') == _brayworth_.moment().format( 'YYYY-MM-DD'), stime);
			received.html( stime);

			let row = $('<div class="row border-bottom border-light py-2" id="<?= strings::rand() ?>" />').appendTo( '#<?= $uidMsgs ?>');
			let cell = $('<div class="col" />').appendTo( row);

			$('<div class="row" />').append( from).appendTo( cell);
			$('<div class="row" />').append( subject).append( received).appendTo( cell);

			row
			.data( 'folder', msg.folder)
			.data( 'message', msg)
			.addClass( 'pointer')
			.on( 'click', function( e) {
				// console.log( 'handle click start');

				let _me = $(this);
				let _data = _me.data();
				let msg = _data.message;

				// console.log( msg);
				$(document).trigger('view-message');
				if ( _data.message.messageid == $('#<?= $uidViewer ?>').data('message')) return;


				let url = _brayworth_.url('<?= $this->route ?>/view?msg=' + encodeURIComponent( _data.message.messageid) + '&folder=' + encodeURIComponent( _data.folder));
				let frame = $('<iframe class="w-100 border-0" style="height: 100%;" />')
				frame.on( 'load', function( e) {
					// console.log( this, e);
					let _frame = this;

					/* build a toolbar */
					let btns = [];
					( function() {
						let btn = $('<button type="button" class="btn btn-sm btn-light d-md-none px-4"><i class="fa fa-angle-left" /></button>');
						btn.on('click', function( e) {
							$(document).trigger('view-message-list');

						});

						btns.push( btn);

					})();

					( function() {
						if ( _brayworth_.browser.isMobileDevice) return;

						let btn = $('<button type="button" class="btn btn-sm btn-light px-4"><i class="fa fa-print" /></button>');
						btn.on('click', function( e) {
							_frame.focus();
							_frame.contentWindow.print();

						});

						btns.push( btn);

					})();

					( function() {
						if ( !_brayworth_.email) return;

						let btn = $('<button type="button" class="btn btn-sm btn-light px-4"><i class="fa fa-reply" /></button>');
						btn.on('click', reply);

						btns.push( btn);

					})();

					let imgs = $('img[data-safe-src]', _frame.contentDocument);
					if ( imgs.length > 0) {
						let btn = $('<button type="button" class="btn btn-sm btn-light"><i class="fa fa-file-image-o" /></button>');
						btn.on('click', function( e) {
							$('img[data-safe-src]', _frame.contentDocument).each( function( i, img) {
								let _img = $(img);

								_img.attr( 'src', _img.attr( 'data-safe-src'));
								_img.removeAttr( 'data-safe-src');

							});

							_frame.focus();
							$(this).remove();

						});

						btns.push( btn);

					}

					let toolbar = $( '<div class="btn-toolbar" />');
					btns.forEach(element => {
						element.appendTo( toolbar);

					});

					toolbar.prependTo( '#<?= $uidViewer ?>');
					frame.css('height','calc(100% - ' + toolbar.height() + 'px');

				});

				frame.attr('src', url);

				$('#<?= $uidViewer ?>')
				.data('message', _data.message.messageid)
				.html('').append( frame);

			})
			.on( 'contextmenu', function( e) {
				if ( e.shiftKey)
					return;

				e.stopPropagation();e.preventDefault();

				_brayworth_.hideContexts();

				let _row = $(this);
				let _data = _row.data();
				let _context = _brayworth_.context();
				let ctrl;

				_context.append( ctrl = $('<a href="#">delete</a>'));
				ctrl.on( 'click', function( e) {
					e.stopPropagation();e.preventDefault();

					// folder : _data.folder,

					_brayworth_.post({
						url : _brayworth_.url('<?= $this->route ?>'),
						data : {
							action : 'delete-message',
							id : _data.message.messageid,

						},

					}).then( function( d) {
						_brayworth_.growl( d);
						_row.remove();

						if ( _data.message.messageid == $('#<?= $uidViewer ?>').data('message')) $('#<?= $uidViewer ?>').trigger('clear');

					});

					_context.close();

				});

				_context.open( e);

			})
			.attr('draggable', true)
			.on( 'dragstart', function( e) {
				// console.log( 'handle drag start');
				e.originalEvent.dataTransfer.setData("text/plain", e.target.id);
				e.originalEvent.dataTransfer.effectAllowed = "move";
				//~ e.dataTransfer.setData("text", e.target.id);
			})
			.on( 'execute-action', function( e, params) {
				let _me = $(this);
				let _data = _me.data();
				// function( uid, folder, action, targetFolder)
				/**
				 * All these action remove the item from this folder
				 * and simultaneously:
				 *  - we may be checking and adding new items,
				 *  - so keep an index of items not to re-add
				 */

				// removed.push( String( uid));
				// console.log( 'added message to removed ' + uid);

				let data = $.extend({
					action :  '',
					folder : _data.folder,
					messageid : _data.message.messageid,
					targetFolder : folder

				}, params);

				// console.log( _data);
				// console.log( data);

				let ctrl = $('<i class="fa fa-spin fa-spinner pull-right" />');
				ctrl.appendTo( $('[from]', _me));

				_me.addClass( 'font-italic');

				_brayworth_.post({
					url : _brayworth_.url('<?= $this->route ?>'),
					data : data,

				}).then( function( d) {
					_brayworth_.growl( d);

					ctrl.remove();	// unnecessary

					_me.remove();

					$(document).trigger('mail-messages-reload', _data.folder);

				});

			})
			;


			// console.log( msg);
			// console.log( '------', time);
			// console.log( row);

		});

	}

	let key = '<?= $this->route ?>' + data.folder + '-lastmessages-';
	let lastMessages = sessionStorage.getItem( key);
	// console.log( key, lastMessages);
	if ( !!lastMessages) {
		_list( JSON.parse( lastMessages));
		sessionStorage.removeItem( key);

	}


	_brayworth_.post({
		url : _brayworth_.url('<?= $this->route ?>'),
		data : data,

	}).then( function( d) {
		if ( 'ack' == d.response) {
			sessionStorage.setItem( key, JSON.stringify( d.messages));
			_list( d.messages);

		}
		else {
			_brayworth_.growl( d);
			// console.log( d);

		}

	});

});

$(document).on( 'view-message-list', function(e) {
	$('#<?= $uidFolders ?>').removeClass('d-md-block').addClass('d-none d-sm-block');
	$('#<?= $uidMsgs ?>').removeClass('d-none d-md-block');
	$('#<?= $uidViewer ?>').addClass('d-none d-md-block');

	// console.log('view-message-list');

});

$(document).on( 'view-message', function(e) {
	$('#<?= $uidFolders ?>').removeClass('d-sm-block').addClass('d-none d-md-block');
	$('#<?= $uidMsgs ?>').addClass('d-none d-md-block');
	$('#<?= $uidViewer ?>').removeClass('d-none d-md-block');

});

$(document).ready( function() {
	let i = $('body > nav').height() + $('body > footer').height();
	$('div[data-role="main-content-wrapper"]').css({
		'height' : 'calc( 100% - ' + i + 'px)'

	})
	$('html, body, div[data-role="main-content-wrapper"] > .row, div[data-role="main-content-wrapper"] > .row > .col').addClass( 'h-100');
	$('div[data-role="content"]').removeClass( 'pt-0 pt-2 pt-3 pt-4 pb-0 pb-1 pb-2 pb-3 pb-4');
	$(document)
	.trigger('mail-messages')
	.trigger('mail-folderlist')
	.trigger('view-message-list');

	$('#<?= $uidViewer ?>')
	.on('clear', function( e) {
			$(this)
				.html('')
				.append('<div class="text-center pt-4 mt-4"><i class="fa fa-envelope-o fa-3x" /></div>');

	}).trigger('clear');

});
</script>