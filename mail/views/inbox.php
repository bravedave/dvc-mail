<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International Public License.
		http://creativecommons.org/licenses/by/4.0/
	*/	?>
<form id="<?= $uidFrm = strings::rand() ?>">
	<input type="hidden" name="user_id" value="<?= $this->data->user_id ?>" />
	<input type="hidden" name="action" />

</form>

<style>
.<?= $uidCSS_dropHere = strings::rand(); ?> { border: 2px solid #ddd; background-color: #eee }
</style>

<div class="row h-100">
	<div class="d-none d-sm-block col-sm-3 col-md-2 h-100" id="<?= $uidFolders = strings::rand() ?>">folders ...</div>
	<div class="col-sm-9 col-md-3 border border-light h-100" style="overflow-y: auto;" id="<?= $uidMsgs = strings::rand() ?>">messages ...</div>
	<div class="d-none d-md-block col-md-7 h-100" id="<?= $uidViewer = strings::rand() ?>"></div>

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
		let _data = $( e.originalEvent.target).data();

		$('#' + data).trigger('execute-action', {
			action : 'move-message',
			targetFolder : _data.folder

		});

	} else { console.dir( 'cannot find src : ', src); }

	e.originalEvent.dataTransfer.clearData();	// Clear the drag data cache (for all formats/types)

	$(this).trigger( 'dragleave');

}	/* end: drag drop move messages */

/**------------------------------------------------ */

$(document).on( 'mail-change-user', function( e, id) {
	$('input[name="user_id"]', '#<?= $uidFrm ?>').val(Number(id));

	$(document)
	.trigger('mail-messages')
	.trigger('mail-folderlist')
	.trigger('view-message-list');

});

$(document).on( 'mail-folderlist', function( e) {
	let frm = $('#<?= $uidFrm ?>');
	let data = frm.serializeFormJSON();
	let folderState = localStorage.getItem('mailFolderState');
	if ( !!folderState) {
		folderState = JSON.parse( folderState);

	}
	else {
		folderState = {}

	}

	let _list = function( folders) {
		// console.log( folders);

		let ul = $('<ul class="list-unstyled small" />');

		let map = '';
		let _list_subfolders = function( i, fldr) {
			// console.log( fldr);

			let ctrl = $('<div class="text-truncate" />').html( fldr.name);
			let li = $('<li class="pt-1 py-md-0 py-3 pointer" />').appendTo(ul).append( ctrl);

			ctrl
			.attr('title', fldr.name)
			.data('folder', fldr.fullname)
			.on( 'click', function( e) {
				e.stopPropagation();

				let _me = $(this);
				let _data = _me.data();

				// console.log( _data.folder);
				$(document).trigger( 'mail-messages', _data.folder);

				//~ $('#submit-folder')
				//~ .val( $(this).data('folder'))
				//~ .closest('form').submit();
			})
			.on( 'contextmenu', function( e) {
				if ( e.shiftKey)
					return;

				e.stopPropagation();e.preventDefault();

				_brayworth_.hideContexts();

				let _me = $(this);
				let _data = _me.data();
				let _context = _brayworth_.context();
				let ctrl

				// console.log( 'contextmenu');
				_context.append( ctrl = $('<a href="#">create subfolder</a>'));
				ctrl.on( 'click', function( e) {
					e.preventDefault();

					_brayworth_.textPrompt({
						title : 'folder name',
						verbatim : 'create a new folder'

					}).then( function( d) {
						if ( /[^a-zA-Z0-9_]/.test(d)) {
							_brayworth.growlError( 'invalid characters detected')
							return;

						}

						let frm = $('#<?= $uidFrm ?>');
						let frmData = frm.serializeFormJSON();
						frmData.action = 'create-folder';
						frmData.parent = _data.folder;
						frmData.folder = d;

						// console.log( _data);
						// console.log( frmData);

						// return;

						// console.log( frmData);	// data from the form
						_brayworth_.post({
							url : _brayworth_.url('<?= $this->route ?>'),
							data : frmData,

						}).then( function( d) {
							_brayworth_.growl( d);
							$(document).trigger('mail-folderlist');

						});

					});

					_context.close();

				});

				_context.append( ctrl = $('<a href="#"><i class="fa fa-trash" />delete folder</a>'));
				ctrl.on( 'click', function( e) {
					e.preventDefault();

					let frm = $('#<?= $uidFrm ?>');
					let frmData = frm.serializeFormJSON();
					frmData.action = 'delete-folder';
					frmData.folder = _data.folder;

					_brayworth_.post({
						url : _brayworth_.url('<?= $this->route ?>'),
						data : frmData,

					}).then( function( d) {
						_brayworth_.growl( d);
						$(document).trigger('mail-folderlist');
						if ( 'nak' == d.response) {
							console.log( d);

						}

					});

					_context.close();

				});

				$(document).trigger( 'mail-folders-context', {
					element : this,
					context : _context

				});

				_context.open( e);

			})
			.on( 'dragover', function( e) { e.preventDefault(); })
			.on( 'dragenter', function( e) { e.preventDefault(); $( this).addClass('<?= $uidCSS_dropHere ?>'); e.originalEvent.dataTransfer.dropEffect = "copy"; })
			.on( 'dragleave', function( e) { e.preventDefault(); $( this).removeClass('<?= $uidCSS_dropHere ?>'); })
			.on( 'drop', MessageDrop)
			;

			if ( !!fldr.subFolders) {
				let caret = $('<i class="fa fa-caret-left fa-fw mt-1 pointer pull-right" />')
				caret.on( 'click', function( e) {
					e.stopPropagation();

					let _me = $(this);
					let sublist = _me.siblings('ul');
					if ( sublist.length > 0) {
						// console.log( sublist);
						if ( _me.hasClass('fa-caret-left')) {
							_me.removeClass('fa-caret-left').addClass( 'fa-caret-down');
							sublist.removeClass( 'd-none');
							folderState[fldr.fullname] = true;

						}
						else {
							_me.removeClass('fa-caret-down').addClass( 'fa-caret-left');
							sublist.addClass( 'd-none');
							folderState[fldr.fullname] = false;

						}

						localStorage.setItem('mailFolderState', JSON.stringify(folderState));

					}

				});

				li.prepend( caret);

				let saveUL = ul;
				ul = $('<ul class="list-unstyled small pl-2" />').appendTo( li);
				if ( !!folderState[fldr.fullname]) {
					caret.removeClass('fa-caret-left').addClass( 'fa-caret-down');

				}
				else {
					ul.addClass('d-none');

				}

				$.each( fldr.subFolders, _list_subfolders);
				ul = saveUL;

			}

		};

		$.each( folders, _list_subfolders);

		$('#<?= $uidFolders ?>').html('<div class="row bg-light text-muted"><div class="col"><h6>folders</h6></div></div>').append( ul);
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
	// console.log( data);	// data from the form
	_brayworth_.post({
		url : _brayworth_.url('<?= $this->route ?>'),
		data : data,

	}).then( function( d) {
		if ( 'ack' == d.response) {
			sessionStorage.setItem( key, JSON.stringify( d.folders));
			_list( d.folders);

		}
		else {
			console.log( d);
			_brayworth_.growl( d);

		}

	});

});

$(document).on( 'mail-messages-reload', function( e, folder) {
	let key = '<?= $this->route ?>' + folder + '-lastmessages-';
	sessionStorage.removeItem( key);

	// console.log('mail-messages-reload', folder);
	$(document).trigger( 'mail-messages', folder);

});

$(document).on( 'mail-messages', function( e, folder) {

	let frm = $('#<?= $uidFrm ?>');
	let data = frm.serializeFormJSON();

	data.action = 'get-messages';
	if ( !!folder) { data.folder = folder; }
	// console.log( folder, data);

	let _list_messages = function( messages) {
		let heading = $('<div class="row bg-light text-muted"><div class="col"><i class="fa fa-refresh fa-spin pull-right pointer" /><h6>'+('undefined' == typeof data.folder ? 'messages' : data.folder)+'</h6></div></div>');
		$('#<?= $uidMsgs ?>').html('').append( heading);

		$('i.fa', heading).on('click', function(e) {
			if ( !!folder)
				$(document).trigger('mail-messages', folder);
			else
				$(document).trigger('mail-messages');

		});
		// console.log( messages);
			//~ if ( !!el.subFolders) {}

		let seed = String( parseInt( Math.random() * 1000000));
		// console.log( seed);

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

			let row = $('<div class="row border-bottom border-light py-2" id="uid_' + String( seed) + '_' + String(seed * i) + '" />').appendTo( '#<?= $uidMsgs ?>');
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

				let user_id = $('input[name="user_id"]', '#<?= $uidFrm ?>').val();
				let params = [
					'msg=' + encodeURIComponent( _data.message.messageid),
					'folder=' + encodeURIComponent( _data.folder)

				];

				if ( Number( user_id) > 0) {
					params.push('user_id=' + user_id);

				}

				let url = _brayworth_.url('<?= $this->route ?>/view?' + params.join('&'));
				let frame = $('<iframe class="w-100 border-0" style="height: 100%;" />')
				frame.on( 'load', function( e) {
					// console.log( this, e);
					let _frame = this;
					let params = {
						message : _data.message,
						toolbar : $( '<div class="btn-toolbar" />'),
						btnClass : 'btn btn-sm btn-light px-3'

					};

					/* build a toolbar */
					let btns = [];
					( function() {
						let btn = $('<button type="button" class="d-md-none"><i class="fa fa-angle-left" /></button>');
						btn
						.addClass( params.btnClass)
						.on('click', function( e) {
							$(document).trigger('view-message-list');

						});

						btns.push( btn);

					})();

					( function() {
						if ( _brayworth_.browser.isMobileDevice) return;

						let btn = $('<button type="button"><i class="fa fa-print" /></button>');
						btn
						.addClass( params.btnClass)
						.on('click', function( e) {
							_frame.focus();
							_frame.contentWindow.print();

						});

						btns.push( btn);

					})();

					( function() {
						if ( !_brayworth_.email) return;

						let btn = $('<button type="button"><i class="fa fa-reply" /></button>');
						btn
						.addClass( params.btnClass)
						.on('click', reply);

						btns.push( btn);

					})();

					let imgs = $('img[data-safe-src]', _frame.contentDocument);
					if ( imgs.length > 0) {
						let btn = $('<button type="button"><i class="fa fa-file-image-o" /></button>');
						btn
						.addClass( params.btnClass)
						.on('click', function( e) {
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

					btns.forEach(element => {
						element.appendTo( params.toolbar);

					});

					toolbar.prependTo( '#<?= $uidViewer ?>');
					frame.css('height','calc(100% - ' + toolbar.height() + 'px');

					// TODO : pass to local software
					$(document).trigger( 'mail-message-toolbar', params)

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
				let defaultFolders = $(document).data( 'default_folders');

				// console.log( _data);

				if ( !!defaultFolders && _data.folder != defaultFolders.Trash) {
					let ctrl= $('<a href="#"><i class="fa fa-trash" />move to '+defaultFolders.Trash+'</a>');
					ctrl.on( 'click', function( e) {
						e.stopPropagation();e.preventDefault();

						$(_row).trigger('execute-action', {
							action : 'move-message',
							targetFolder : defaultFolders.Trash

						});

						_context.close();

					});
					_context.append( ctrl);

				}

				$(document).trigger( 'mail-messages-context', {
					element : this,
					context : _context

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

				let frm = $('#<?= $uidFrm ?>');
				let data = frm.serializeFormJSON();
				data.folder = _data.folder;
				data.messageid = _data.message.messageid;
				data.targetFolder = folder;

				$.extend( data, params);

				// console.log( _data);

				let ctrl = $('<i class="fa fa-spin fa-spinner pull-right" />');
				ctrl.appendTo( $('[from]', _me));

				_me.addClass( 'font-italic');

				// console.log( data);	// data from the form
				_brayworth_.post({
					url : _brayworth_.url('<?= $this->route ?>'),
					data : data,	//

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
	$('#<?= $uidMsgs ?>').data('folder', folder);
	if ( !!lastMessages) {
		// console.log( 'lastMessages - ' + data.folder);
		_list_messages( JSON.parse( lastMessages));
		sessionStorage.removeItem( key);

	}


	$('i.fa-refresh', '#<?= $uidMsgs ?>').addClass('fa-spin');

	_brayworth_.post({
		url : _brayworth_.url('<?= $this->route ?>'),
		data : data,	// data from the form

	}).then( function( d) {
		if ( 'ack' == d.response) {
			sessionStorage.setItem( key, JSON.stringify( d.messages));
			// console.log( 'messages - ' + data.folder);
			if ( folder == $('#<?= $uidMsgs ?>').data('folder')) {
				_list_messages( d.messages);

			}

		}
		else {
			_brayworth_.growl( d);
			// console.log( d);

		}

		$('i.fa-refresh', '#<?= $uidMsgs ?>').removeClass('fa-spin');

	});

});

$(document).on( 'toggle-view', function() {
	let view = $(document).data('view');
	let key = '<?= $this->route ?>-view';
	let lastView = sessionStorage.getItem( key);

	if ( 'condensed' == view) {
		view = 'wide';
		sessionStorage.setItem( key, view);
	}
	else if ( 'wide' == view) {
		view = 'condensed';
		sessionStorage.setItem( key, view);

	}
	else {
		view = sessionStorage.getItem( key);
		if ( !view) view = 'wide';

	}

	// console.log( key, view);

	if ( 'condensed' == view) {
		$(document).data('view', view);
		$('#<?= $uidFolders ?>').removeClass('d-sm-block');
		$('#<?= $uidMsgs ?>').removeClass('col-sm-9');
		$('#<?= $uidViewer ?>').removeClass('col-md-7').addClass('col-md-9');

	}
	else if ( 'wide' == view) {
		$(document).data('view', view);
		$('#<?= $uidFolders ?>').addClass('d-sm-block');
		$('#<?= $uidMsgs ?>').addClass('col-sm-9');
		$('#<?= $uidViewer ?>').removeClass('col-md-9').addClass('col-md-7');

	}
	// console.log( $(document).data('view'));

})
.trigger( 'toggle-view');

$(document).on( 'view-message-list', function(e) {
	let view = $(document).data('view');

	if ( 'condensed' != view) {
		$('#<?= $uidFolders ?>').removeClass('d-md-block').addClass('d-sm-block');

	}

	$('#<?= $uidMsgs ?>').removeClass('d-none d-md-block');
	$('#<?= $uidViewer ?>').addClass('d-none d-md-block');

	// console.log('view-message-list');

});

$(document).on( 'view-message', function(e) {
	let view = $(document).data('view');
	if ( 'condensed' != view) {
		$('#<?= $uidFolders ?>').removeClass('d-sm-block').addClass('d-md-block');

	}

	$('#<?= $uidMsgs ?>').addClass('d-none d-md-block');
	$('#<?= $uidViewer ?>').removeClass('d-none d-md-block');

});

$(document).ready( function() {
	$(document)
	.data('default_folders', <?= json_encode( $this->data->default_folders) ?>)
	.data('route', '<?= $this->route ?>');

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