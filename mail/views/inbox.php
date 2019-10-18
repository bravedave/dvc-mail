<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International Public License.
		http://creativecommons.org/licenses/by/4.0/
	*/

	$keyLastFolders = sprintf('%s-lastfolders', $this->route);
	$activeMessage = 'open-message';
	$unseen = '<span class="pull-left text-primary font-weight-bold" style="margin-left: -.8rem; font-size: 2rem; line-height: .5;" unseen>&bull;</span>';
	// $unseen = '<i class="fa fa-circle text-primary" style="margin-left: -14px;" unseen></i>';

	?>
<form id="<?= $uidFrm = strings::rand() ?>">
	<input type="hidden" name="user_id" value="<?= $this->data->user_id ?>" />
	<input type="hidden" name="page" value="0" />
	<input type="hidden" name="action" />

</form>

<style>
.open-message { color: #004085; background-color: #cce5ff; }
.<?= $uidCSS_dropHere = strings::rand(); ?> { border: 2px solid #ddd; background-color: #eee }
</style>

<div class="row h-100">
	<div class="d-none d-sm-block col-sm-3 col-md-2 h-100" id="<?= $uidFolders = strings::rand() ?>" data-role="mail-folder-list">folders ...</div>
	<div class="col-sm-9 col-md-3 border border-light h-100" style="overflow-y: auto;" id="<?= $uidMsgs = strings::rand() ?>" data-role="mail-messages-list">messages ...</div>
	<div class="d-none d-md-block col-md-7 h-100" id="<?= $uidViewer = strings::rand() ?>" data-role="mail-message-viewer"></div>

</div>
<script>
$(document).on( 'mail-change-user', function( e, id) {
	$('input[name="user_id"]', '#<?= $uidFrm ?>').val(Number(id));

	$('input[name="page"]','<?= $uidFrm ?>').val( 0);

	$(document)
	.trigger('mail-messages')
	.trigger('mail-folderlist')
	.trigger('mail-view-message-list');

});

$(document).on( 'mail-clear-reloader', function( e) {
	(function( i) {
		if ( !!i) {
			window.clearTimeout( i);
			$(document).removeData( 'mail-messages-reloader');

		}

	})( $(document).data( 'mail-messages-reloader'));

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

		let ul = $('<ul class="list-unstyled" />');

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
				$('input[name="page"]','<?= $uidFrm ?>').val( 0);
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
			.on( 'drop', function( e) {
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

			});

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

		$('#<?= $uidFolders ?>').html('<div class="row bg-light text-muted"><div class="col"><h6>folders</h6></div></div>');
		$.each( folders, _list_subfolders);
		$('#<?= $uidFolders ?>').append( ul);
		//~ console.log( folders);

		$(document).trigger('mail-folderlist-complete');

	}

	let lastFolders = sessionStorage.getItem( '<?= $keyLastFolders ?>');
	// console.log( key, lastFolders);
	if ( !!lastFolders) {
		_list( JSON.parse( lastFolders));
		sessionStorage.removeItem( '<?= $keyLastFolders ?>');

	}

	data.action = 'get-folders';
	// console.log( data);	// data from the form
	_brayworth_.post({
		url : _brayworth_.url('<?= $this->route ?>'),
		data : data,

	}).then( function( d) {
		if ( 'ack' == d.response) {
			sessionStorage.setItem( '<?= $keyLastFolders ?>', JSON.stringify( d.folders));
			_list( d.folders);

		}
		else {
			console.log( d);
			_brayworth_.growl( d);

		}

	});

});

$(document).on( 'mail-messages-reload', function( e, folder) {
	let key = '<?= $this->route ?>-lastmessages-';
	if ( 'undefined' != typeof folder) {
		key += folder + '-';

	}

	sessionStorage.removeItem( key);

	$(document).trigger( 'mail-messages', folder);

});

$(document).on( 'mail-messages', function( e, folder) {

	$(document).trigger( 'mail-clear-reloader');

	let reply = function( _data) {
		let frame = $('iframe', '#<?= $uidViewer ?>');
		if ( frame.length < 1) return;

		let _document = frame[0].contentDocument;
		let _body = $('div[message]', _document);

		let _wrap = $('<div data-role="original-message" style="border-left: 2px solid #eee; padding-left: 5px;" />');
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

		if ( !/^re: /i.test( _subject)) _subject = 're: ' + _subject;

		let j = {
			subject : _subject,
			message : _brayworth_.browser.isMobileDevice ? _wrap.text() : '<br /><br />' + _wrap[0].outerHTML

		};

		// console.log( _data);
		// console.log( _data.message);
		// console.log( this);
		if ( /^reply/.test( $(this).data('role')) && _to != undefined) {
			j.to = _to;
			j.in_reply_to = _data.message.messageid;
			j.in_reply_to_msg = _data.message.uid;
			j.in_reply_to_folder = _data.message.folder;

			// console.log( j);
			//~ console.log( 'reply', j.in_reply_to_msg = container.data('uid'));

		}
		else if ( /^forward/.test( $(this).data('role'))) {
			j.forward_msg = _data.message.uid;
			j.forward_folder = _data.message.folder;
			//~ console.log( 'forward', j.forward_msg = container.data('uid'));

			// console.log( j);
			j.callback = function() {
				this.GetAttachmentsFromAnotherMessage( j.forward_msg, j.forward_folder );

			}

		}

		if ( _brayworth_.email) {
			_brayworth_.email.activate( j);

		}
		else {
			_brayworth_.modal({
				title:'alert',
				text:'no email program to run ..'

			})

		}
		// console.log( _to, _time, _body);

		return;

		var wrap = $('<div />');
		if ( $(this).data('role') == 'reply-fast')
			wrap.append('<p>' + $('#reply-fast-response').val() + '</p>');

		$('[data-role="attachment-control"],  [data-role="tag-control"]', _wrap).each(function() {
			$(this).remove();
		});

		if ( $(this).data('role') == 'reply-all-button') {
			var e, a = [];
			e = container.data('to');
			if ( e != undefined) a.push(e);

			e = container.data('cc');
			if ( e != undefined) a.push(e);

			if ( a.length) j.cc = a.join(',');

		}

	}

	let frm = $('#<?= $uidFrm ?>');
	let data = frm.serializeFormJSON();

	data.action = 'get-messages';
	if ( !!folder) { data.folder = folder; }
	// console.log( folder, data);

	let heading = $('<div class="row bg-light text-muted" />');
	let col = $('<div class="col" />').appendTo( heading);
	let h = $('<h6/>').html('undefined' == typeof data.folder ? 'messages' : data.folder).appendTo( col);
	let defaultFolders = $(document).data( 'default_folders');
	// console.log( defaultFolders);

	$('#<?= $uidMsgs ?>').html('').append( heading);

	$('<i class="fa fa-fw fa-angle-left pull-right pointer" title="previous page" />')
	.prependTo( col)
	.on('click', function(e) {
		let v = Number( $('input[name="page"]','#<?= $uidFrm ?>').val());
		if ( v > 0) {
			v --;
			$('input[name="page"]','#<?= $uidFrm ?>').val( v);

			if ( !!folder)
				$(document).trigger('mail-messages', folder);
			else
				$(document).trigger('mail-messages');

		}

	});

	let page = Number( $('input[name="page"]','#<?= $uidFrm ?>').val());
	if ( page > 0) {
		$('<span class="badge badge-pill badge-light pull-right" />').html('#' + (page+1)).prependTo( col);

	}
	$('<i class="fa fa-fw fa-angle-right pull-right pointer" title="next page" />')
	.prependTo( col)
	.on('click', function(e) {
		let v = Number( $('input[name="page"]','#<?= $uidFrm ?>').val());
		v ++;
		$('input[name="page"]','#<?= $uidFrm ?>').val( v);

		if ( !!folder)
			$(document).trigger('mail-messages', folder);
		else
			$(document).trigger('mail-messages');

	});

	$('<i class="fa fa-fw fa-spinner fa-spin pull-right pointer" />')
	.prependTo( col)
	.on('click', function(e) {
		if ( !!folder)
			$(document).trigger('mail-messages', folder);
		else
			$(document).trigger('mail-messages');

	});

	let _list_messages = function( messages, cacheData) {
		let seed = String( parseInt( Math.random() * 1000000));
		$('[msgid]').each( function( i, el) {
			$(el).data('seen', false);

		});

		let firstMsg = $('[msgid]');
		$.each( messages, function( i, msg) {
			let row = $('[msgid="'+msg.messageid+'"]');
			if ( row.length > 0) {
				row.data('seen', true);
				// console.log('found : '+msg.messageid);
				if ( 'no' == msg.seen) {
					let _unseen = $('[unseen]', row);
					if ( _unseen.length == 0 ) {
						$('[from]', row).append( '<?= $unseen ?>');

					}

				}
				else {
					$('[unseen]', row).remove();

				}


				return;

			}
			// console.log('build : [msgid="'+msg.messageid+'"]');

			// console.log( msg);
			// msg.folder ==
			let email = msg.from;
			if ( msg.folder == defaultFolders.Sent) email = msg.to;
			let from = $('<div class="col text-truncate font-weight-bold" from />').html( email);
			if ( 'no' == msg.seen) {
				$('<?= $unseen ?>').prependTo( from);

			}

			let received = $('<div class="col-3 pl-0 text-right text-truncate small" />');
			let subject = $('<div class="col-9 text-truncate" subject />').html( msg.subject).attr( 'title', msg.subject);

			let time = _brayworth_.moment( msg.received);
			let stime = time.format( 'YYYY-MM-DD') == _brayworth_.moment().format( 'YYYY-MM-DD') ? time.format('LT') : time.format('l')
			// console.log( time.format( 'YYYY-MM-DD') == _brayworth_.moment().format( 'YYYY-MM-DD'), stime);
			received.html( stime);

			let rowID = 'uid_' + String( seed) + '_' + String(seed * i);
			row = $('<div class="row border-bottom border-light py-2" />');
			row.attr('id', rowID);
			row.data('seen', true);
			row.attr('msgid', msg.messageid);

			if ( 'undefined' == typeof $('#<?= $uidViewer ?>').data('first')) {
				$('#<?= $uidViewer ?>').data('first', rowID);

			}

			if ( firstMsg.length > 0) {
				row.insertBefore( firstMsg[0]);

			}
			else {
				row.appendTo( '#<?= $uidMsgs ?>');

			}

			let cell = $('<div class="col" />').appendTo( row);

			$('<div class="row" />').append( from).appendTo( cell);
			$('<div class="row" />').append( subject).append( received).appendTo( cell);

			row
			.data( 'folder', msg.folder)
			.data( 'message', msg)
			.addClass( 'pointer')
			.on( 'view', function( e) {
				let _me = $(this);
				let _data = _me.data();
				let msg = _data.message;

				let _next = _me.next();
				if ( _next.length > 0) {
					$('#<?= $uidViewer ?>').data('next', _next.attr('id'))

				}
				else {
					$('#<?= $uidViewer ?>').removeData('next');

				}

				let _prev = _me.prev();
				if ( _prev.length > 0) {
					$('#<?= $uidViewer ?>').data('prev', _prev.attr('id'))

				}
				else {
					$('#<?= $uidViewer ?>').removeData('prev');

				}

				// console.log( msg);
				$(document).trigger('mail-view-message');
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

					frame[0].contentWindow.setTimeout( () => {
						let id = params.message.messageid;
								// console.log( id);
								// console.log( $('[msgid="'+id+'"]'));

						$('[msgid="'+id+'"]').trigger('mark-seen');

					}, 3000);


					/* build a toolbar */
					let btns = [];
					( function() {
						let btn = $('<button type="button" class="d-md-none"><i class="fa fa-angle-left" /></button>');
						btn
						.addClass( params.btnClass)
						.on('click', function( e) {
							$('#<?= $uidViewer ?>').removeData('next');
							$(document).trigger('mail-view-message-list');

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
						let btn = $('<button type="button" data-role="reply"><i class="fa fa-mail-reply" /></button>');
						btn
						.addClass( params.btnClass)
						.on('click', () => { reply.call( btn, _data)});

						btns.push( btn);

					})();

					( function() {
						let btn = $('<button type="button" data-role="forward"><i class="fa fa-mail-forward" /></button>');
						btn
						.addClass( params.btnClass)
						.on('click', () => { reply.call( btn, _data)});

						btns.push( btn);

					})();

					( function() {
						let defaultFolders = $(document).data( 'default_folders');

						if ( !!defaultFolders && params.message.folder != defaultFolders.Trash) {
							let btn = $('<button type="button"><i class="fa fa-trash" /></button>');
							btn
							.attr('title', 'move to '+defaultFolders.Trash)
							.addClass( params.btnClass)
							.on( 'click', function( e) {
								// _me is the active row
								// console.log( _me[0]);
								// return;
								let id = params.message.messageid;
								// console.log( id);
								// console.log( $('[msgid="'+id+'"]'));

								$('[msgid="'+id+'"]').trigger('execute-action', {
									action : 'move-message',
									targetFolder : defaultFolders.Trash

								});

								$('#<?= $uidViewer ?>').trigger('clear');

							});

							btns.push( btn);

						}

					})();

					( function() {
						let lastFolders = sessionStorage.getItem( '<?= $keyLastFolders ?>');
						// console.log( key, lastFolders);
						if ( !!lastFolders) {
							let btn = $('<button type="button"><i class="fa fa-flip-horizontal fa-share-square-o" /></button>');
							btn
							.attr('title', 'move ')
							.addClass( params.btnClass)
							.on( 'click', function( e) {
								let select = $('<select class="form-control" />');

								let level = 0;
								/**
								 * you can set this:
								 *
								 * $(document).data('mail-archive-location', 'Archives/2019')
								 */
								let archiveFolder = String( $(document).data('mail-archive-location'));

								let _list_subfolders = function( i, fldr) {

									let opt = $('<option />')
									.html( '&nbsp;'.repeat(level) + fldr.name)
									.val(fldr.fullname);

									if ( archiveFolder == fldr.fullname) {
										opt.prop( 'selected', true);

									}

									opt.appendTo( select);

									if ( !!fldr.subFolders) {
										level ++;
										$.each( fldr.subFolders, _list_subfolders);
										level --;

									}

								};

								$.each( JSON.parse( lastFolders), _list_subfolders);

								let ig = $('<div class="input-group input-group-sm" />');
								select.appendTo( ig);

								let btn = $('<button type="button" class="btn btn-primary">move</button>');
								$('<div class="input-group-prepend" />').append( btn).prependTo( ig);

								// console.log( _data.message);

								// $('<input type="hidden" name="folder" />').val( _data.message.folder).appendTo( frm);

								btn
								.on( 'click', function(e) {

									let target = select.val();
									// console.log( target);

									let id = params.message.messageid;
									$('[msgid="'+id+'"]').trigger('execute-action', {
										action : 'move-message',
										targetFolder : target

									});

								})

								ig.insertAfter( this);
								$(this).remove();

							});

							btns.push( btn);

						}

					})();

					$('a[data-role="extra-recipients"]', _frame.contentDocument).each( function( i, el) {
						$(el).on( 'click', function( e) {
							e.stopPropagation();e.preventDefault();

							let _me = $(this);
							let _data = _me.data();
							let target = _data.target;
							$('#' + target, _frame.contentDocument).css('display','');
							// console.log( target, $('#' + target, _frame.contentDocument)[0]);
							_me.remove();	// ciao ..

						});;

					});

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

					params.toolbar.prependTo( '#<?= $uidViewer ?>');
					frame.css('height','calc(100% - ' + params.toolbar.height() + 'px)');

					$(document)
					.trigger( 'mail-message-toolbar', params)
					.trigger( 'mail-message-loaded', {
						message : _data.message,
						window : _frame.contentDocument
					});

				});

				frame.attr('src', url);

				$('#<?= $uidViewer ?>')
				.data('message', _data.message.messageid)
				.html('').append( frame);

				$('> .row', _me.parent()).each( function() {
					$(this).removeClass( '<?= $activeMessage ?>');

				});

				_me.addClass('<?= $activeMessage ?>');

			})
			.on( 'click', function( e) {
				$(this).trigger('view');

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

						_row.trigger('delete');

						// _row.trigger('execute-action', {
						// 	action : 'move-message',
						// 	targetFolder : defaultFolders.Trash

						// });

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
			.on( 'delete', function( e) {
				let _row = $(this);
				let _data = _row.data();
				let defaultFolders = $(document).data( 'default_folders');

				if ( !!defaultFolders && _data.folder != defaultFolders.Trash) {
					_row.trigger('execute-action', {
						action : 'move-message',
						targetFolder : defaultFolders.Trash

					});

				}

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

				// console.log( params);
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

					if ( _data.message.messageid == $('#<?= $uidViewer ?>').data('message')) {
						$('#<?= $uidViewer ?>').trigger('clear');

					}

					$(document).trigger('mail-messages-loader', _data.folder);

				});

			})
			.on('mark-seen', function( e) {
				let _me = $(this);
				let _data = _me.data();

				let frm = $('#<?= $uidFrm ?>');
				let data = frm.serializeFormJSON();
				data.folder = _data.folder;
				data.messageid = _data.message.messageid;
				data.action = 'mark-seen';


				_brayworth_.post({
					url : _brayworth_.url('<?= $this->route ?>'),
					data : data,

				}).then( function( d) {
					if ( 'ack' == d.response) {
						$('[unseen]', _me).remove();

					}
					else {
						_brayworth_.growl( d);

					}

				});

			});

		});

		$('[msgid]').each( function( i, el) {
			let _el = $(el);
			if ( !_el.data('seen')) {
				_el.remove();

			}

		});

		if ( !cacheData) $(document).trigger('mail-message-list-complete');

	}

	let key = '<?= $this->route ?>-lastmessages-';
	if ( 'undefined' != typeof data.folder) {
		key += data.folder + '-';

	}
	if ( page > 0) key += page;

	let lastMessages = sessionStorage.getItem( key);
	// console.log( key, lastMessages);
	$('#<?= $uidMsgs ?>').data('folder', folder);
	if ( !!lastMessages) {
		// console.log( 'lastMessages - ' + data.folder);
		_list_messages( JSON.parse( lastMessages), true);
		sessionStorage.removeItem( key);

	}

	$(document)
	.off('mail-messages-loader')
	.on('mail-messages-loader', function() {

		$('i.fa-refresh', '#<?= $uidMsgs ?>').removeClass('fa-refresh').addClass('fa-spinner fa-spin');
		$(document).trigger( 'mail-clear-reloader');

		_brayworth_.post({
			url : _brayworth_.url('<?= $this->route ?>'),
			data : data,	// data from the form

		}).then( function( d) {
			if ( 'ack' == d.response) {
				sessionStorage.setItem( key, JSON.stringify( d.messages));
				// console.log( 'messages - ' + data.folder);
				if ( folder == $('#<?= $uidMsgs ?>').data('folder')) {
					if ( data.page == Number( $('input[name="page"]','#<?= $uidFrm ?>').val())) {
						_list_messages( d.messages);
						$('i.fa-spinner', '#<?= $uidMsgs ?>').addClass('fa-refresh').removeClass('fa-spinner fa-spin');

						if ( 0 == data.page) {
							$(document).trigger( 'mail-clear-reloader');
							$(document).data( 'mail-messages-reloader', window.setTimeout(() => {
								let key = '<?= $this->route ?>-lastmessages-';
								if ( 'undefined' != typeof folder) {
									key += folder + '-';

								}

								sessionStorage.removeItem( key);
								$(document).trigger('mail-messages-loader');

							}, 20000));

						}

					}

				}

			}
			else {
				_brayworth_.growl( d);
				// console.log( d);
				// $('i.fa-refresh', '#<?= $uidMsgs ?>').removeClass('fa-spin');
				$('i.fa-spinner', '#<?= $uidMsgs ?>').removeClass('fa-spinner fa-spin').addClass('fa-refresh');

			}

		});

	})
	.trigger('mail-messages-loader');

});

$(document).on( 'mail-set-view', function() {
	let view = $(document).data('view');
	let focus = $(document).data('focus');

	if ( 'condensed' == view) {
		$('#<?= $uidFolders ?>').attr('class', 'd-none h-100');

		if ('message-view' == focus) {
			$('#<?= $uidMsgs ?>').attr( 'class', 'd-none d-md-block col-md-3 border border-light h-100');
			$('#<?= $uidViewer ?>').attr( 'class', 'col-md-9');

		}
		else {
			// message-list
			$('#<?= $uidMsgs ?>').attr( 'class', 'col-md-3 border border-light h-100');
			$('#<?= $uidViewer ?>').attr( 'class', 'd-none d-md-block col-md-9');

		}

	}
	else if ( 'wide' == view) {
		$('#<?= $uidFolders ?>').attr('class', 'd-none d-sm-block col-sm-3 col-md-2 h-100');

		if ('message-view' == focus) {
			$('#<?= $uidMsgs ?>').attr( 'class', 'd-none d-md-block col-md-3 border border-light h-100');
			$('#<?= $uidViewer ?>').attr( 'class', 'col-md-7');

		}
		else {
			// message-list
			$('#<?= $uidMsgs ?>').attr( 'class', 'col-sm-9 col-md-3 border border-light h-100');
			$('#<?= $uidViewer ?>').attr( 'class', 'd-none d-md-block col-md-7');

		}

	}

	// $(document).trigger( 'mail-view-state');

});

$(document).on( 'mail-view-state', function() {
	console.table({
		view : $(document).data('view'),
		focus : $(document).data('focus'),
		folders : $('#<?= $uidFolders ?>').attr('class'),
		list : $('#<?= $uidMsgs ?>').attr('class'),
		viewer : $('#<?= $uidViewer ?>').attr('class')

	});

});

$(document).on( 'mail-toggle-view', function() {
	let view = $(document).data('view');
	let key = '<?= $this->route ?>-view';

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
		if ( ['condensed','wide'].indexOf(view) < 0) view = 'wide';

	}

	// console.log( key, $(document).data('view'), view);
	$(document).data('view', view);
	$(document).trigger('mail-set-view');

});

$(document).on( 'mail-view-message-list', function( e) {
	$(document).data('focus', 'message-list');
	$(document).trigger('mail-set-view');

});

$(document).on( 'mail-view-message', function( e) {
	$(document).data('focus', 'message-view');
	$(document).trigger('mail-set-view');

});

$(document).on( 'mail-message-load-first', function() {
	$('#<?= $uidMsgs ?> > div[msgid]').first().trigger('view');

});

$(document).on( 'mail-message-load-next', function() {
	let nid = $('#<?= $uidViewer ?>').data('next');
	if ( 'undefined' != typeof nid) {
		$('#<?= $uidViewer ?>').removeData('next').removeData('prev');
		// console.log( 'nid', nid);
		let _row = $('#' + nid);
		if ( _row.length > 0) {
			_row.trigger('view');

		}

	}

});

$(document).on( 'mail-message-load-prev', function() {
	let nid = $('#<?= $uidViewer ?>').data('prev');
	if ( 'undefined' != typeof nid) {
		$('#<?= $uidViewer ?>').removeData('next').removeData('prev');
		// console.log( 'nid', nid);
		let _row = $('#' + nid);
		if ( _row.length > 0) {
			_row.trigger('view');

		}

	}

});

$('#<?= $uidViewer ?>').on('clear', function( e) {
	$(this)
	.html('')
	.append('<div class="text-center pt-4 mt-4"><i class="fa fa-envelope-o fa-3x" /></div>');

	if ( !_brayworth_.browser.isMobileDevice && 'yes' == $(document).data('autoloadnext')) {
		$(document).trigger( 'mail-message-load-next');

	}

});

if ( !_brayworth_.browser.isMobileDevice) {
	$(document).on('keydown', function( e) {
		/**
		*	arrow keys are only triggered by onkeydown, not onkeypress
		*
		*	keycodes are:
		*		left = 37
		*		up = 38
		*		right = 39
		*		down = 40
		*/

		// console.log( e);

		if ( 38 == e.keyCode) {
			e.stopPropagation();
			$(document).trigger( 'mail-message-load-prev');

		}
		else if ( 40 == e.keyCode) {
			e.stopPropagation();
			$(document).trigger( 'mail-message-load-next');
			// console.log( e.keyCode, 'mail-message-load-next');

		}
		else if ( 39 == e.keyCode) {
			e.stopPropagation();
			$('iframe', '#<?= $uidViewer ?>').focus();

		}
		else if ( 46 == e.keyCode) {
			e.stopPropagation();
			( function( data) {
				// _row.trigger('delete');
				if ( 'undefined' != typeof data.message) {
					let row = $('[msgid="'+data.message+'"]');
					if ( row.length > 0) {
						row.trigger('delete');

					}

				}

			})( $('#<?= $uidViewer ?>').data());

		}

	});

}

$(document).on('mail-clear-viewer', function( e) {
	$('#<?= $uidViewer ?>').trigger('clear');

});

$(document).ready( function() {
	$(document)
	.data('default_folders', <?= json_encode( $this->data->default_folders) ?>)
	.data('route', '<?= $this->route ?>')
	.data('autoloadnext', '<?= ( currentUser::option('email-autoloadnext') == 'yes' ? 'yes' : 'no' ) ?>');

	let i = $('body > nav').height() + $('body > footer').height();
	$('div[data-role="main-content-wrapper"]').css({
		'height' : 'calc( 100% - ' + i + 'px)'

	})
	$('html, body, div[data-role="main-content-wrapper"] > .row, div[data-role="main-content-wrapper"] > .row > .col').addClass( 'h-100');
	$('div[data-role="content"]').removeClass( 'pt-0 pt-2 pt-3 pt-4 pb-0 pb-1 pb-2 pb-3 pb-4');

	$(document)
	.trigger('mail-messages')
	.trigger('mail-folderlist');
	// console.log('init');

	$(document).trigger('mail-toggle-view')
	// console.log('init-2');

	$(document).trigger('mail-view-message-list');
	// console.log('init-3');

	$(document).trigger('mail-clear-viewer');

	if ( !_brayworth_.browser.isMobileDevice) {
		$('#<?= $uidMsgs ?>').focus();

	}

	$(document).trigger('mail-load-complete');

});
</script>