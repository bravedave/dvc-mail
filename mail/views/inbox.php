<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

$keyLastFolders = sprintf('%s-lastfolders', $this->route);
$activeMessage = 'open-message';

?>
<form id="<?= $uidFrm = strings::rand() ?>">
  <input type="hidden" name="user_id" value="<?= $this->data->user_id ?>">
  <input type="hidden" name="page" value="0">
  <input type="hidden" name="action">

</form>

<style>
  .open-message {
    color: #004085;
    background-color: #cce5ff;
  }

  <?= sprintf(
    '.%s {
      border: 2px solid #ddd;
      background-color: #eee;
    }',
    $uidCSS_dropHere = strings::rand()
  ) ?> ::-webkit-scrollbar {
    width: .5em;
  }

  ::-webkit-scrollbar-track {
    -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
    box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
  }

  ::-webkit-scrollbar-thumb {
    background-color: darkgrey;
    outline: 1px solid slategrey;
  }

  .folderlist:not(.searching-disabled) input[searchThisFolder] {
    display: none;
  }

  @media (max-width: 575px) {

    body.hide-nav-bar>nav,
    body.hide-nav-bar>footer {
      display: none;
    }

  }

  [data-role="content"] {
    padding: 0 15px !important;
  }
</style>

<style id="<?= $uid = strings::rand() ?>"></style>
<script>
  $(document).on('resize-main-content-wrapper', function(e) {
    let i = $('body > nav').outerHeight() + $('body > footer').outerHeight();

    let css = [
      '@media (max-width: 575px) {',
      '	body:not(.hide-nav-bar) div[data-role="main-content-wrapper"] { height : calc( 100vh - ' + i + 'px) }',
      '	body.hide-nav-bar div[data-role="main-content-wrapper"] { height : 100vh }',
      '}',

      '@media (min-width: 576px) {',
      '	body div[data-role="main-content-wrapper"] { height : calc( 100vh - ' + i + 'px) }',
      '}'

    ];

    $('#<?= $uid ?>').text(css.join("\n"));

  });
</script>


<div class="row h-100">
  <div class="d-none" id="<?= $uidSearchAll = strings::rand() ?>" style="overflow: auto;" data-role="mail-search-all">
    <form id="<?= $uidSearchAll ?>_form">
      <input type="hidden" name="action" value="search-all-messages">

      <button type="button" class="close" id="<?= $uid = strings::rand() ?>">&times;</button>
      <script>
        $('#<?= $uid ?>').on('click', function(e) {
          $(document).trigger('mail-default-view');
        });
      </script>

      <h6>Search All Folders</h6>

      <div class="form-row mb-2">
        <div class="col">
          <input type="search" class="form-control" name="term" placeholder="term" required>

        </div>

      </div>

      <div class="form-row mb-2">
        <div class="col-md-6">
          <label for="<?= $uid = strings::rand() ?>">from</label>
          <input type="date" class="form-control" name="from" id="<?= $uid ?>">

        </div>

        <div class="col-md-6 pb-md-0 pb-1">
          <label for="<?= $uid = strings::rand() ?>">to</label>
          <input type="date" class="form-control" name="to" id="<?= $uid ?>">

        </div>

      </div>

      <div class="form-row mb-2">
        <div class="col">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" name="search-body" value="yes" id="<?= $uid = strings::rand() ?>">

            <label class="form-check-label" for="<?= $uid ?>">Search Body</label>

          </div>

        </div>

        <div class="col text-right">
          <button class="btn btn-outline-primary">search</button>

        </div>

      </div>

    </form>

    <div class="row" id="<?= $uidSearchAll ?>_buttons">
      <div class="col">
        [<a href="#" id="<?= $uid = strings::rand() ?>set">set all</a>]
        [<a href="#" id="<?= $uid ?>unset">unset all</a>]

      </div>

    </div>

    <script>
      (_ => {
        $('#<?= $uid ?>set').on('click', e => {
          e.stopPropagation();
          e.preventDefault();
          $('input[type="checkbox"]', '#<?= $uidSearchAll ?>_folders').each((i, el) => $(el).prop('checked', true));

        });

        $('#<?= $uid ?>unset').on('click', e => {
          e.stopPropagation();
          e.preventDefault();
          $('input[type="checkbox"]', '#<?= $uidSearchAll ?>_folders').each((i, el) => $(el).prop('checked', false));

        });

      })(_brayworth_);
    </script>

    <div class="form-group row">
      <div id="<?= $uidSearchAll ?>_folders" class="col"></div>

    </div>

  </div>

  <div class="d-none" id="<?= $uidFolders = strings::rand() ?>" style="overflow: auto;" data-role="mail-folder-list">folders ...</div>
  <div class="d-none" id="<?= $uidMsgs = strings::rand() ?>" style="overflow-y: auto;" data-role="mail-messages-list">messages ...</div>
  <div class="d-none" id="<?= $uidViewer = strings::rand() ?>" data-role="mail-message-viewer"></div>

</div>
<script>
  (_ => {
    $(document).on('mail-change-user', function(e, id) {
      $('input[name="user_id"]', '#<?= $uidFrm ?>').val(Number(id));
      $('input[name="page"]', '<?= $uidFrm ?>').val(0);

      $(document)
        .trigger('mail-messages')
        .trigger('mail-folderlist')
        .trigger('mail-view-message-list');

      let frm = $('#<?= $uidFrm ?>');
      let frmData = frm.serializeFormJSON();
      frmData.action = 'get-default-folders';
      _.post({
        url: _.url('<?= $this->route ?>'),
        data: frmData

      }).then(function(d) {
        if ('ack' == d.response) {
          // console.log(d);
          $(document).data('default_folders', d.data);

        }

      })

    });

    $(document).on('mail-clear-reloader', function(e) {
      (i => {
        if (!!i) {
          window.clearTimeout(i);
          $(document).removeData('mail-messages-reloader');

        }

      })($(document).data('mail-messages-reloader'));

    });

    let _learnAsHam = {
      available: false,
      checked: false,
      folder: '',
      reset: function() {
        this.available = false;
        this.checked = false;
        this.folder = '';

      }

    };

    let featureLearnAsHam = () => {
      return new Promise(resolve => {
        if (_learnAsHam.checked) {
          resolve(_learnAsHam);

        } else {
          let frm = $('#<?= $uidFrm ?>');
          let data = frm.serializeFormJSON();

          data.action = 'get-folders-learnasham';

          _.post({
            url: _.url('<?= $this->route ?>'),
            data: data,

          }).then(d => {
            if ('ack' == d.response) {
              // console.log( d);
              _learnAsHam.checked = true;
              _learnAsHam.available = '' != String(d.folder.fullname);
              _learnAsHam.folder = d.folder.fullname;
              resolve(_learnAsHam);

            }

          });

        }

      });

    }

    let _learnAsSpam = {
      available: false,
      checked: false,
      folder: '',
      reset: function() {
        this.available = false;
        this.checked = false;
        this.folder = '';

      }

    };

    let featureLearnAsSpam = () => {
      return new Promise(resolve => {
        if (_learnAsSpam.checked) {
          resolve(_learnAsSpam);

        } else {
          let frm = $('#<?= $uidFrm ?>');
          let data = frm.serializeFormJSON();

          data.action = 'get-folders-learnasspam';
          // console.log( data.action);

          _.post({
            url: _.url('<?= $this->route ?>'),
            data: data,

          }).then(d => {
            if ('ack' == d.response) {
              _learnAsSpam.checked = true;
              _learnAsSpam.available = '' != String(d.folder.fullname);
              _learnAsSpam.folder = d.folder.fullname;
              resolve(_learnAsSpam);

            }

          });

        }

      });

    }

    let _list = (folders, cacheData) => {
      // console.log( folders);

      // general reset on learning features
      _learnAsHam.reset();
      _learnAsSpam.reset();

      let ul = $('<ul class="nav flex-column folderlist small"></ul>');
      $('#<?= $uidSearchAll ?>_folders').html(''); // a .col

      let keys = {};
      let searchKeys = {};

      let map = '';
      let uidx = 0;
      let _list_subfolders = (i, fldr) => {
        // console.log( fldr);

        let ctrl = $('<div class="text-truncate"></div>').html(fldr.name);
        if ('LearnAsHam' == fldr.name) {
          // console.log( fldr);

          _learnAsHam.checked = true;
          _learnAsHam.available = '' != String(fldr.fullname);
          _learnAsHam.folder = fldr.fullname;

        }

        if ('LearnAsSpam' == fldr.name) {
          // console.log( fldr);

          _learnAsSpam.checked = true;
          _learnAsSpam.available = '' != String(fldr.fullname);
          _learnAsSpam.folder = fldr.fullname;

        }

        let searchCtrl = $('<div class="form-check"></div>');
        let chkId = '<?= $uidSearchAll ?>_chk_' + String(++uidx);
        searchCtrl.append($('<input type="checkbox" class="form-check-input" name="path" checked>')
          .attr('id', chkId)
          .data('folder', fldr.fullname));
        searchCtrl.append($('<label class="form-check-label"></label>').html(fldr.name).attr('for', chkId));

        let li = $('<li class="nav-item pointer py-1"></li>')
          .appendTo(ul)
          .append(ctrl);

        if (/LearnAsSpam|LearnAsHam/i.test(fldr.name)) {
          li.addClass('d-none');
        }

        let searchLI = $('<div class="col"></div>').append(searchCtrl);
        searchLI.appendTo($('<div class="row"></div>').appendTo('#<?= $uidSearchAll ?>_folders'))

        keys[fldr.fullname] = li;
        searchKeys[fldr.fullname] = searchLI;

        let idx = fldr.fullname.lastIndexOf(fldr.delimiter);
        if (idx > 0) {
          let realPath = fldr.fullname.substring(0, idx).trim();
          if (keys.hasOwnProperty(realPath)) {
            let realName = fldr.fullname.substring(idx + 1).trim();
            // console.log( realPath, ':', realName);
            ctrl.html(realName);
            // console.log( realPath, keys[realPath][0]);
            let _ul = $('> ul', keys[realPath]);
            if (_ul.length == 0) {

              let folderState = localStorage.getItem('mailFolderState');
              folderState = !!folderState ? JSON.parse(folderState) : {};

              let caret = $('<i class="bi bi-caret-left pointer float-right"></i>');
              caret.on('click', function(e) {
                e.stopPropagation();
                // console.log( 'ckic');

                let _me = $(this);
                let sublist = _me.siblings('ul');
                if (sublist.length > 0) {
                  // console.log( sublist);
                  if (_me.hasClass('bi-caret-left')) {
                    _me.removeClass('bi-caret-left').addClass('bi-caret-down');
                    sublist.removeClass('d-none');
                    folderState[fldr.fullname] = true;

                  } else {
                    _me.removeClass('bi-caret-down').addClass('bi-caret-left');
                    sublist.addClass('d-none');
                    folderState[fldr.fullname] = false;

                  }

                  localStorage.setItem('mailFolderState', JSON.stringify(folderState));

                }

              });

              caret.prependTo(keys[realPath]);

              _ul = $('<ul class="nav flex-column pl-2"></ul>').appendTo(keys[realPath]);
              if (!!folderState[fldr.fullname]) {
                caret.removeClass('bi-caret-left').addClass('bi-caret-down');

              } else {
                _ul.addClass('d-none');

              }

            }
            li.appendTo(_ul);

          } else {
            li.appendTo(ul);

          }

          if (searchKeys.hasOwnProperty(realPath)) {
            let realName = fldr.fullname.substring(idx + 1).trim();
            // console.log( realPath, ':', realName);
            $('label', searchCtrl).html(realName);

            // slight indent on new owner ...
            $('<div class="row" search-parameter-row><div class="col-auto"></div></div>')
              .append(searchLI)
              .appendTo(searchKeys[realPath]);

          }
          // else {
          // searchLI.appendTo( '#<?= $uidSearchAll ?>_folders');

          // }

        } else {
          li.appendTo(ul);
          // searchLI.appendTo( '#<?= $uidSearchAll ?>_folders');

        }
        /** [ recon flag ] */


        ctrl
          .prepend($('<input type="checkbox" class="mr-1" searchThisFolder>').attr('data-folder', fldr.fullname).on('click', e => e.stopPropagation()))
          .attr('title', fldr.name)
          .data('folder', fldr.fullname)
          .on('click', function(e) {
            e.stopPropagation();

            let _me = $(this);
            let _data = _me.data();

            // console.log( _data.folder);
            $('input[name="page"]', '#<?= $uidFrm ?>').val(0);
            $(document).trigger('mail-messages', _data.folder);

            //~ $('#submit-folder')
            //~ .val( $(this).data('folder'))
            //~ .closest('form').submit();
          })
          .on('contextmenu', function(e) {
            if (e.shiftKey)
              return;

            e.stopPropagation();
            e.preventDefault();

            _.hideContexts();

            let _me = $(this);
            let _data = _me.data();
            let _context = _.context();

            // console.log( 'contextmenu');
            _context.append($('<a href="#">create subfolder</a>').on('click', function(e) {
              e.preventDefault();

              _.textPrompt({
                title: 'folder name',
                verbatim: 'create a new folder'

              }).then(d => {
                if (/[^a-zA-Z0-9_\- ]/.test(d)) {
                  _.ask.warning({
                    title: 'warning',
                    text: 'invalid characters detected'

                  });
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
                $('#<?= $uidFolders ?>').trigger('spin');

                _.post({
                  url: _.url('<?= $this->route ?>'),
                  data: frmData,

                }).then(function(d) {
                  _.growl(d);
                  $(document).trigger('mail-folderlist-reload');

                });

              });

              _context.close();

            }));

            let docData = $(document).data();
            let fldrCheck = {
              folders: docData.default_folders,
              default: function(fldr) {
                let b = false

                $.each(this.folders, function(i, s) {
                  if (fldr == s) {
                    b = true;
                    return false;

                  }

                });

                return b;

              }

            };

            if (_data.folder == docData.default_folders.Trash) {
              _context.append($('<a href="#"><i class="bi bi-trash"></i>empty trash</a>').on('click', function(e) {
                e.preventDefault();

                let frm = $('#<?= $uidFrm ?>');
                let frmData = frm.serializeFormJSON();
                frmData.action = 'empty-trash';
                frmData.folder = _data.folder;

                $('#<?= $uidFolders ?>').trigger('spin');

                _.post({
                  url: _.url('<?= $this->route ?>'),
                  data: frmData,

                }).then(function(d) {
                  _.growl(d);
                  $(document).trigger('mail-folderlist-reload');
                  if ('nak' == d.response) {
                    console.log(d);

                  }

                });

                _context.close();

              }));

            } else if (!fldrCheck.default(_data.folder)) {
              _context.append($('<a href="#"><i class="bi bi-trash"></i>delete folder</a>').on('click', function(e) {
                e.preventDefault();

                let frm = $('#<?= $uidFrm ?>');
                let frmData = frm.serializeFormJSON();
                frmData.action = 'delete-folder';
                frmData.folder = _data.folder;

                $('#<?= $uidFolders ?>').trigger('spin');

                _.post({
                  url: _.url('<?= $this->route ?>'),
                  data: frmData,

                }).then(function(d) {
                  _.growl(d);
                  $(document).trigger('mail-folderlist-reload');
                  if ('nak' == d.response) {
                    console.log(d);

                  }

                });

                _context.close();

              }));

            }


            $(document).trigger('mail-folders-context', {
              element: this,
              context: _context

            });

            _context.open(e);

          })
          .on('dragover', function(e) {
            e.preventDefault();
          })
          .on('dragenter', function(e) {
            e.preventDefault();
            $(this).addClass('<?= $uidCSS_dropHere ?>');
            e.originalEvent.dataTransfer.dropEffect = "copy";
          })
          .on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('<?= $uidCSS_dropHere ?>');
          })
          .on('drop', function(e) {
            // console.log( 'handle drop');
            e.preventDefault();

            // Get the data, which is the id of the drop target
            let data = e.originalEvent.dataTransfer.getData("text");
            if (data == '')
              return;

            // console.log( 'MessageDrop', data);
            // return;

            let src = $('#' + data);
            if (src.length) {
              let _data = $(e.originalEvent.target).data();

              $('#' + data).trigger('execute-action', {
                action: 'move-message',
                targetFolder: _data.folder

              });

            } else {
              console.dir('cannot find src : ', src);
            }

            e.originalEvent.dataTransfer.clearData(); // Clear the drag data cache (for all formats/types)

            $(this).trigger('dragleave');

          });

        if (!!fldr.subFolders) {
          $.each(fldr.subFolders, _list_subfolders);
        }

      };

      $('#<?= $uidFolders ?>').html('<div class="row bg-light text-muted"><div class="col d-flex"><h6 class="text-truncate pt-1 mb-1">folders</h6></div></div>');
      $('<button type="button" class="btn btn-sm ml-auto pl-2 pr-0"><i class="bi bi-arrow-repeat"></i></button>')
        .on('click', function(e) {
          $('i.bi-arrow-repeat', this).addClass('bi-spin');
          $(document).trigger('mail-folderlist-reload');

        })
        .appendTo('#<?= $uidFolders ?> > div > div.col');

      $('#<?= $uidFolders ?>').on('spin', function(e) {
        $('i.bi-arrow-repeat', this).addClass('bi-spin');
      });

      $.each(folders, _list_subfolders);
      $('#<?= $uidFolders ?>').append(ul);
      //~ console.log( folders);

      if (!cacheData) $(document).trigger('mail-folderlist-complete');

    };

    $(document).on('mail-folderlist', function(e) {
      let lastFolders = sessionStorage.getItem('<?= $keyLastFolders ?>');
      // console.log( key, lastFolders);
      if (!!lastFolders) {
        _list(JSON.parse(lastFolders), true);
        sessionStorage.removeItem('<?= $keyLastFolders ?>');

      }

      $(document).trigger('mail-folderlist-reload');

    });

    $(document).on('mail-folderlist-reload', e => {
      let frm = $('#<?= $uidFrm ?>');
      let data = frm.serializeFormJSON();
      data.action = 'get-folders';
      // console.log( data);	// data from the form

      $('#<?= $uidFolders ?>').trigger('spin');

      _.post({
        url: _.url('<?= $this->route ?>'),
        data: data,

      }).then(function(d) {
        if ('ack' == d.response) {
          sessionStorage.setItem('<?= $keyLastFolders ?>', JSON.stringify(d.folders));
          _list(d.folders, false);

        } else {
          console.log(d);
          _.growl(d);

        }

      });

    });

    $(document).on('mail-messages-reload', function(e, folder) {
      let key = '<?= $this->route ?>-lastmessages-';
      if ('undefined' != typeof folder) {
        key += folder + '-';

      }

      sessionStorage.removeItem(key);

      $(document).trigger('mail-messages', folder);

    });

    $(document).data('default_folders', <?= json_encode($this->data->default_folders) ?>);

    // process mail messages into a list
    let seed = String(parseInt(Math.random() * 1000000));
    let seedI = 0;

    const encodeHTMLEntities = function(html) {
      let txt = document.createElement("span");
      txt.textContent = html;
      return txt.innerHTML;

    }

    const reply = function(_data) {
      let frame = $('iframe', '#<?= $uidViewer ?>');
      if (frame.length < 1) return;

      let _document = frame[0].contentDocument;
      let _body = $('div[message]', _document);

      let _wrap = $('<div data-role="original-message" style="border-left: 2px solid #eee; padding-left: 5px;"></div>');
      _wrap.html(_body.clone().html());

      // cleanup empty p tags
      $('p', _wrap).each((i, el) => {
        let _el = $(el);
        if (_el.html().length == 0)
          _el.remove();
      });

      let role = $(this).data('role');

      let _subject = $('[data-role="subject"]', _document).text().trim();
      let _to = $('[data-role="reply-to"]', _document).text();
      // console.log('to (reply-to)', _to);
      if (!_to) {
        _to = $('[data-role="from"]', _document).text();
        // console.log('to (from)', _to);
      }

      let _time = $('[data-role="time"]', _document).text();

      /*
        write information about the message into the start of the message
        more formal for a forwarded message, replys are more casual */
      let _prepender = [];

      if ('forward' == role) {
        let _tos = [];
        let _ccs = [];
        $('[data-role="to"]', _document).each((i, el) => {
          let _data = $(el).data();
          let em = String(_data.email).getEmail();
          if ('' != em && _tos.indexOf(em) < 0) _tos.push(em);
        });

        $('[data-role="cc"]', _document).each((i, el) => {
          let _data = $(el).data();
          let em = String(_data.email).getEmail();
          if ('' != em && _ccs.indexOf(em) < 0) _ccs.push(_data.email);
        });

        _prepender.unshift('---------------------');
        _prepender.unshift('<strong>Subject:</strong> ' + _subject);
        if (_ccs.length > 0) _prepender.unshift('<strong>Cc:</strong> ' + encodeHTMLEntities(_ccs.join(', ')));
        if (_tos.length > 0) _prepender.unshift('<strong>To:</strong> ' + encodeHTMLEntities(_tos.join(', ')));

        let __time = $('[data-role="time"]', _document).text();
        if (__time != '') _prepender.unshift('<strong>Sent:</strong> ' + __time);

        if (_tos.length > 0) _prepender.unshift('<strong>From:</strong> ' + encodeHTMLEntities(_to));

      } else if ('' != String(_time)) {
        if ('' != String(_to)) {
          if (_.isDateValid(_time)) {
            let m = _.dayjs(_time);
            _time = m.format('lll');
          }
          _prepender.push('from ' + encodeHTMLEntities(_to) + ' - ' + _time + '<br><br>');

        } else {
          _prepender.push('message on ' + _time + ' contained:');
        }
      } else if ('' != String(_to)) {
        if (_.isDateValid(_time)) {
          let m = _.dayjs(_time);
          _time = m.format('lll');
        }
        _prepender.push('from ' + encodeHTMLEntities(_to) + '<br><br>');
      }

      // console.table(_prepender);
      _wrap.prepend(_prepender.join('<br>'));

      if ('forward' == role) {
        if (!/^fwd: /i.test(_subject)) _subject = 'fwd: ' + _subject;

      } else {
        if (!/^re: /i.test(_subject)) _subject = 're: ' + _subject;

      }

      let _frm = $('#<?= $uidFrm ?>');
      let _frm_data = _frm.serializeFormJSON();
      // console.log( _frm_data);

      // message: _.browser.isMobileDevice ? '' : '<br><br>' + _wrap[0].outerHTML,
      let j = {
        message: '<br><br>' + _wrap[0].outerHTML,
        original: _wrap[0].outerHTML,
        subject: _subject,
        user_id: _frm_data.user_id

      };

      // console.log( _data);
      // console.log( _data.message);
      // console.log( this);
      // console.log( role);
      if (/^reply(-all)?/.test(role)) {
        j.in_reply_to = _data.message.messageid;
        j.in_reply_to_msg = _data.message.uid;
        j.in_reply_to_folder = _data.message.folder;

        if (_to != undefined) {
          let em = String(_to).getEmail();
          if ('' != em && em != _.currentUser.email) {
            j.to = _to;

          } else {
            console.log('not setting to to my email !');
          }

        }

        if ('reply-all' == role && _to != undefined) {
          let _gots = [];
          let _ccs = [];
          $('[data-role="to"]', _document).each((i, el) => {
            let _el = $(el);
            let _data = _el.data();

            let em = String(_data.email).getEmail();
            if ('' != em && em != _.currentUser.email && _to != em && _gots.indexOf(em) < 0) {
              _gots.push(em);
              _ccs.push(_data.email);
            }

          });

          $('[data-role="cc"]', _document).each((i, el) => {
            let _el = $(el);
            let _data = _el.data();

            let em = String(_data.email).getEmail();
            if ('' != em && em != _.currentUser.email && _to != em && _gots.indexOf(em) < 0) {
              _gots.push(em);
              _ccs.push(_data.email);
            }
          });

          if (_ccs.length > 0) j.cc = _ccs.join(',');

        }

      } else if ('forward' == role) {
        j.forward_msg = _data.message.uid;
        j.forward_folder = _data.message.folder;
        //~ console.log( 'forward', j.forward_msg = container.data('uid'));

        // console.log( j);
        j.callback = function() {
          this.GetAttachmentsFromAnotherMessage(j.forward_msg, j.forward_folder);

        }

      }

      if (!!_.email && !!_.email.activate) {
        _.email.activate(j).then(ec => {
          if ('function' == typeof ec.onActivate) {
            ec.onActivate();
          }
        });

      } else {
        // ( function(html) {
        // 	let txt = document.createElement("span");
        // 	txt.textContent = html;
        // 	console.log( '<txt.innerHTML>');
        // 	console.log( txt.innerHTML);
        // 	console.log( '</txt.innerHTML>');

        // })( j.original);

        // console.log(role, _to);
        console.log(j);
        _.ask.warning({
          title: 'alert',
          text: 'no email program to run ..'

        });

      }
      // console.log( _to, _time, _body);

    };

    let _list_message_row = (msg, withBulkSelector) => {
      // console.log( msg);
      let defaultFolders = $(document).data('default_folders');

      let email = msg.from;
      if (msg.folder == defaultFolders.Sent) email = msg.to;
      let from = $('<div class="col d-flex" from></div>');

      $('<span class="text-primary font-weight-bold d-none" style="margin-left: -.8rem; font-size: 2rem; line-height: .5;" unseen>&bull;</span>').appendTo(from);

      from
        .append($('<div class="text-truncate font-weight-bold mr-auto"></div>').html(email))
        .attr('title', email);

      $('<i class="bi bi-reply mx-1 fade" title="you have replied to this message" answered></i>').appendTo(from);
      $('<i class="bi bi-reply bi-flip-horizontal mx-1 fade" title="your forwarded this message" forwarded></i>').appendTo(from);

      let selector = $('<input class="mt-1" type="checkbox" selector></i>');
      if (withBulkSelector) selector.appendTo(from);

      if ('no' == msg.seen) $('[unseen]', from).removeClass('d-none');
      if ('yes' == msg.forwarded) $('[forwarded]', from).addClass('show');
      if ('yes' == msg.answered) $('[answered]', from).addClass('show');
      //----------------------------------------------------


      let received = $('<div class="col-3 pl-0 text-right text-truncate small"></div>');
      let subject = $('<div class="col-9 text-truncate" subject></div>').html(msg.subject).attr('title', msg.subject);

      let time = _.dayjs(msg.received);
      let stime = time.format('YYYY-MM-DD') == _.dayjs().format('YYYY-MM-DD') ? time.format('LT') : time.format('l')
      // console.log( time.format( 'YYYY-MM-DD') == _.dayjs().format( 'YYYY-MM-DD'), stime);
      received.html(stime);

      let rowID = 'uid_' + String(seed) + '_' + String(seed * seedI++);
      row = $('<div class="row border-bottom border-light py-2"></div>');
      row
        .attr('id', rowID)
        .attr('uid', msg.uid)
        .data('seen', true)
        .data('read', msg.seen)
        .data('received', time.format('YYYYMMDDHHmmss'));

      $('<div class="col-2 d-none text-center bg-danger text-white pt-2" trash-control><i class="bi bi-trash mt-2"></i></div>').appendTo(row);
      let cell = $('<div class="col" message-control></div>').appendTo(row);

      $('<div class="row"></div>').append(from).appendTo(cell);
      $('<div class="row"></div>').append(subject).append(received).appendTo(cell);

      row
        .data('folder', msg.folder)
        .data('message', msg)
        .addClass('pointer')
        .on('set-next', function(e) {
          let _me = $(this);
          let _next = _me.next();
          if (_next.length > 0) {
            $('#<?= $uidViewer ?>').data('next', _next.attr('uid'))
            // console.log( 'next =', _next.attr('uid'));

          } else {
            $('#<?= $uidViewer ?>').removeData('next');

          }

        })
        .on('set-previous', function(e) {
          let _me = $(this);
          let _prev = _me.prev();
          if (_prev.length > 0) {
            $('#<?= $uidViewer ?>').data('prev', _prev.attr('uid'))

          } else {
            $('#<?= $uidViewer ?>').removeData('prev');

          }

        })
        .on('view', function(e) {
          let _me = $(this);
          let _data = _me.data();
          let msg = _data.message;

          _me.trigger('set-next').trigger('set-previous');

          $(document).trigger('mail-view-message');
          if (_data.message.uid == $('#<?= $uidViewer ?>').data('uid')) {
            $(document).trigger('mail-view-message-set-url', $('#<?= $uidViewer ?>').data('url'));
            return;

          }

          let user_id = $('input[name="user_id"]', '#<?= $uidFrm ?>').val();
          let params = [
            'folder=' + encodeURIComponent(_data.folder)

          ];

          // console.log( _data.message);

          if ('' != String(_data.message.uid)) {
            params.push('uid=' + encodeURIComponent(_data.message.uid));

          }

          if (Number(user_id) > 0) {
            params.push('user_id=' + user_id);

          }

          let url = _.url('<?= $this->route ?>/view?' + params.join('&'));
          let frame = $('<iframe class="w-100 border-0 pl-sm-1" style="height: 100%;"></iframe>');
          frame.on('load', function(e) {
            // console.log( this, e);
            let _frame = this;
            let params = {
              message: _data.message,
              toolbar: $('<div class="btn-group flex-grow-1 btn-group-sm"></div>'),
              btnClass: 'btn btn-light px-3'

            };
            params.toolbar = $('<div class="btn-group btn-group-sm"></div>');
            // params.btnClass = 'btn btn-secondary px-3';
            if (Number(user_id) > 0) {
              params.user_id = user_id;

            }

            frame[0].contentWindow.setTimeout(() => {
              if ('' != String(params.message.uid)) {
                // console.log( 'issue mark seen by uid', '[id="'+params.message.uid+'"]');
                $('[uid="' + params.message.uid + '"]').trigger('mark-seen');

              }

            }, 2000);

            /* build a toolbar */
            let btns = [];
            (function() {
              let btn = $('<button type="button" class="d-md-none"><i class="bi bi-arrow-left-short bi-2x"></i></button>');
              btn
                .addClass(params.btnClass)
                .on('click', function(e) {
                  $('#<?= $uidViewer ?>').removeData('next');
                  $(document).trigger('mail-view-message-list');

                });

              btns.push(btn);

            })();

            (() => { // trash button
              if (!!defaultFolders && params.message.folder != defaultFolders.Trash) {
                let btn = $('<button type="button" class="flex-shrink-1" data-role="trash"><i class="bi bi-trash"></i></button>');
                btn
                  .attr('title', 'move to ' + defaultFolders.Trash)
                  .addClass(params.btnClass)
                  .on('click', function(e) {
                    // _me is the active row
                    // console.log( params.message);
                    // return;
                    let uid = params.message.uid;
                    // console.log( id);
                    // console.log( $('[uid="'+uid+'"]').first());
                    // return;

                    $('[uid="' + uid + '"]').first().trigger('execute-action', {
                      action: 'move-message',
                      targetFolder: defaultFolders.Trash

                    });

                    $('#<?= $uidViewer ?>').trigger('clear');
                    $(document).trigger('mail-view-message-list');

                  });

                btns.push(btn);

              }

            })();

            (() => {
              let lastFolders = sessionStorage.getItem('<?= $keyLastFolders ?>');
              // console.log( key, lastFolders);
              if (!!lastFolders) {
                let btn = $('<button type="button" data-role="move"><i class="bi bi-folder-symlink"></i></button>');
                btn
                  .attr('title', 'move ')
                  .addClass(params.btnClass)
                  .on('click', function(e) {
                    let select = $('<select class="form-control"></select>');

                    let level = 0;
                    /**
                     * you can set this:
                     *
                     * $(document).data('mail-archive-location', 'Archives/2019')
                     */
                    let archiveFolder = String($(document).data('mail-archive-location'));

                    let _list_subfolders = function(i, fldr) {

                      let opt = $('<option></option>')
                        .html('&nbsp;'.repeat(level) + fldr.name)
                        .val(fldr.fullname);

                      if (archiveFolder == fldr.fullname) {
                        opt.prop('selected', true);

                      }

                      opt.appendTo(select);

                      if (!!fldr.subFolders) {
                        level++;
                        $.each(fldr.subFolders, _list_subfolders);
                        level--;

                      }

                    };

                    $.each(JSON.parse(lastFolders), _list_subfolders);

                    let ig = $('<div class="input-group input-group-sm"></div>');
                    select.appendTo(ig);

                    let btn = $('<button type="button" class="btn btn-primary">move</button>');
                    $('<div class="input-group-prepend"></div>').append(btn).prependTo(ig);

                    // console.log( _data.message);

                    // $('<input type="hidden" name="folder">').val( _data.message.folder).appendTo( frm);

                    btn
                      .on('click', function(e) {

                        let target = select.val();
                        // console.log( target);

                        let uid = params.message.uid;
                        // console.log( id);
                        // console.log( $('[uid="'+uid+'"]').first());
                        // return;

                        $('[uid="' + uid + '"]').first().trigger('execute-action', {
                          action: 'move-message',
                          targetFolder: target

                        });

                      })

                    ig.insertAfter(this);
                    $(this).remove();

                  });

                btns.push(btn);

              }

            })();

            const _rbbtn_Click = function() {
              reply.call(this, _data)
            };

            btns.push(
              $('<button type="button" data-role="reply"><i class="bi bi-reply"></i></button>')
              .addClass(params.btnClass)
              .on('click', _rbbtn_Click)
            );


            btns.push(
              $('<button type="button" data-role="reply-all"><i class="bi bi-reply-all"></i></button>')
              .addClass(params.btnClass)
              .on('click', _rbbtn_Click)

            );

            btns.push(
              $('<button type="button" data-role="forward"><i class="bi bi-reply" style="display: flex; transform: scale(-1,1);"></i></button>')
              .addClass(params.btnClass)
              .on('click', _rbbtn_Click)
            );

            (function() {
              if (_.browser.isMobileDevice) return;

              let btn = $('<button type="button"><i class="bi bi-box-arrow-up-right"></i></button>');

              btn
                .attr('title', 'pop out')
                .data('url', url)
                .addClass(params.btnClass)
                .on('click', function(e) {
                  let _me = $(this);
                  let _data = _me.data();

                  window.open(_data.url, '_blank', 'toolbar=yes,menubar=no');
                  $('#<?= $uidViewer ?>').trigger('clear');
                  $(document).trigger('mail-view-message-list');

                });

              btns.push(btn);

            })();

            (function() {
              if (_.browser.isMobileDevice) return;

              let btn = $('<button type="button"><i class="bi bi-printer"></i></button>');
              btn
                .addClass(params.btnClass)
                .on('click', function(e) {
                  _frame.focus();
                  _frame.contentWindow.print();

                });

              btns.push(btn);

            })();

            (function() {
              let f = function() {
                /** called on the control which specifies the target to expose */
                let _me = $(this);
                let _data = _me.data();
                let target = _data.target;
                $('#' + target, _frame.contentDocument).css('display', '');
                // console.log( target, $('#' + target, _frame.contentDocument)[0]);
                _me.remove(); // ciao ..

              };

              $('a[data-role="extra-recipients"]', _frame.contentDocument).each(function(i, el) {
                <?php
                if ('yes' == currentUser::option('email-expand-recipients')) {  ?>
                  f.call(this);
                <?php
                } else { /* unless this option is 'yes' then this is wrapped in a trigger (default) */ ?>
                  $(el).on('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    f.call(this);

                  });;

                <?php
                }  ?>

              });

            })();

            let imgs = $('img[data-safe-src]', _frame.contentDocument);
            if (imgs.length > 0) {
              let btn = $('<button type="button" title="show images"><i class="bi bi-file-image"></i></button>');
              btn
                .addClass(params.btnClass)
                .on('click', function(e) {
                  $('img[data-safe-src]', _frame.contentDocument).each((i, img) => {
                    let _img = $(img);

                    _img.attr('src', _img.attr('data-safe-src'));
                    _img.removeAttr('data-safe-src');

                  });

                  _frame.focus();
                  $(this).remove();

                });

              btns.push(btn);

            }

            btns.forEach(element => {
              element.appendTo(params.toolbar);
            });

            (toolbar => {
              if (_.browser.isMobileDevice) {
                let row = $('<div class="row"></div>').prependTo('#<?= $uidViewer ?>');
                $('<div class="col bg-secondary"></div>').appendTo(row).append(toolbar);

              } else {
                toolbar.prependTo('#<?= $uidViewer ?>');

              }


            })($('<div class="btn-toolbar" role="toolbar" aria-label="Mail Toolbar"></div>').append(params.toolbar));

            // let h = params.toolbar.height();
            // let f = $('body>footer');
            // if ( f.length > 0) h += f.height();
            if (_.browser.isMobileDevice) {
              $('div[message] img', _frame.contentDocument).each(function(i, el) {
                let _el = $(el);
                let width = String(_el.css('width')).replace(/px$/, '');
                if ('IMG' == el.tagName || (Number(width) > 0 && Number(width) > window.innerWidth)) {
                  // console.log(width);
                  _el.css({
                    'width': '',
                    'max-width': '100%'

                  });

                }

              });

            } else {

              frame.css('height', 'calc(100% - 2.3rem)');

              $(_frame.contentDocument).on('keydown', function(e) {
                if (27 == e.keyCode) {
                  window.focus();

                }

              });

            }

            $(document)
              .trigger('mail-message-toolbar', params)
              .trigger('mail-message-loaded', {
                form: $('#<?= $uidFrm ?>')[0],
                message: _data.message,
                window: _frame.contentDocument

              });

            <?php if (currentUser::option('email-enable-quick-reply') == 'yes') { ?>
                (function() {
                  let frame = $('iframe', '#<?= $uidViewer ?>');
                  if (frame.length > 0) {
                    let _document = frame[0].contentDocument;
                    let _body = $('div[message]', _document);
                    let _to = $('[data-role="from"]', _document).text();
                    let _subject = $('[data-role="subject"]', _document).text().trim();
                    if (!/^re: /i.test(_subject)) _subject = 're: ' + _subject;

                    /**---------------------------------------------------------------------------- */

                    let form = $('<form></form>').appendTo('#<?= $uidViewer ?>');
                    let row = $('<div class="row mx-0"></div>').appendTo(form);
                    let col = $('<div class="col position-relative"></div>').appendTo(row);
                    let ta = $('<textarea class="form-control pt-2" rows="3" required></textarea>').appendTo(col);

                    let ig = $('<div class="input-group input-group-sm position-absolute" style="top: -1.2rem; left: 27px; width: 360px; opacity: .7"><div class="input-group-prepend"><div class="input-group-text">to</div></div></div>')
                      .appendTo(col);

                    $('<input type="text" readonly class="form-control form-control-sm">')
                      .val(_to)
                      .appendTo(ig);

                    frame.css('height', 'calc( 100% - ' + row.height() + 'px - 3rem)');

                    ta.attr('placeholder', 'quick reply not enabled yet');

                    let btn = $('<button type="submit" class="btn btn-light btn-sm position-absolute rounded-circle" style="top: -1.2rem; right: 27px;"><i class="bi bi-cursor"></i></button>');
                    form.on('submit', function(e) {

                      let _wrap = $('<div data-role="original-message" style="border-left: 2px solid #eee; padding-left: 5px;"></div>');
                      _wrap.html(_body.clone().html());

                      $('p', _wrap).each(function() {
                        let _me = $(this);
                        if (_me.html().length == 0)
                          _me.remove();

                      });

                      let _time = $('[data-role="time"]', _document).text();
                      if ('' != String(_time)) {
                        if ('' != String(_to)) {
                          if (_.isDateValid(_time)) {
                            let m = _.dayjs(_time);
                            _time = m.format('lll');

                          }
                          _wrap.prepend('from ' + encodeHTMLEntities(_to) + ' on ' + _time + '<br><br>');

                        } else {
                          _wrap.prepend('message on ' + _time + ' contained:');

                        }

                      }

                      let frm = $('#<?= $uidFrm ?>');
                      let frmData = frm.serializeFormJSON();

                      frmData.action = 'send-email';
                      frmData.to = _to;
                      frmData.subject = _subject;
                      frmData.message = '';
                      frmData.in_reply_to = _data.message.messageid;
                      frmData.in_reply_to_msg = _data.message.uid;
                      frmData.in_reply_to_folder = _data.message.folder;

                      let _m = $('<div></div>').append($('<p></p>').text(ta.val())).append(_wrap);
                      frmData.message = _m.html();

                      /**------------------- */
                      console.table(frmData);
                      /**------------------- */

                      return false;


                    });
                    btn.appendTo(col);

                    /**---------------------------------------------------------------------------- */

                  }

                })();
            <?php }   ?>

          });

          frame.attr('src', url);

          // let frameWrap = $('<div class="pl-sm-1 h-100"></div>').append( frame);


          $('#<?= $uidViewer ?>')
            .data('uid', _data.message.uid)
            .data('url', url)
            .html('')
            .append(frame);

          $(document).trigger('mail-view-message-set-url', url);

          $('> .row', _me.parent()).each(function() {
            $(this).removeClass('<?= $activeMessage ?>');

          });

          _me.addClass('<?= $activeMessage ?>');
          window.setTimeout(() => _me[0].scrollIntoViewIfNeeded({
            behavior: 'smooth'
          }), 200)

          // console.log(_me[0]);

        })
        .on('click', function(e) {
          $(this).trigger('view');

        })

      selector
        .data('rowid', rowID)
        .on('click', function(e) {
          e.stopPropagation();

          $('#<?= $uidMsgs ?>').trigger('expose-bulk-controls');

        });

      return row;

    };

    let _list_messages = (messages, cacheData, withBulkSelector) => {
      $('> [uid]', '#<?= $uidMsgs ?>').each((i, el) => $(el).data('seen', false));

      $.each(messages, (i, msg) => {
        let row = $('[uid="' + msg.uid + '"]');
        if (row.length > 0) {
          row.data('seen', true);
          row.data('read', msg.seen);
          // console.log('found : '+msg.uid);
          if ('no' == msg.seen) {
            let _unseen = $('[unseen]', row);
            if (_unseen.length == 0) {
              $('[unseen]', row).removeClass('d-none');

            }

          } else {
            $('[unseen]', row).addClass('d-none');

          }

          if ('yes' == msg.answered) {
            let _answered = $('[answered]', row);
            if (_answered.length == 0) {
              $('[answered]', row).addClass('show');

            }

          }

          return;

        }
        // console.log('build : [uid="'+msg.uid+'"]');

        // console.log( msg);
        // msg.folder ==
        row = _list_message_row(msg, withBulkSelector);
        let rowID = row.attr('id');

        /**
         *	find the next location to insert based on time
         */
        let time = _.dayjs(msg.received);
        let nextMsg = false;
        $('> [uid]', '#<?= $uidMsgs ?>').each((i, el) => {
          // $(el).data('seen', false);
          let _el = $(el);
          let _data = _el.data();

          if (_data.received < time.format('YYYYMMDDHHmmss')) {
            nextMsg = _el;
            return false;

          }

        });

        if (!!nextMsg) {
          row.insertBefore(nextMsg);

        } else {
          row.appendTo('#<?= $uidMsgs ?>');

        }

        row
          .on('contextmenu', function(e) {
            if (e.shiftKey)
              return;

            e.stopPropagation();
            e.preventDefault();

            _.hideContexts();

            let _row = $(this);
            let _data = _row.data();
            let _context = _.context();
            let defaultFolders = $(document).data('default_folders');

            _context.append(
              $('<a href="#"></a>')
              .html('yes' == String(_data.read) ? 'mark unseen' : 'mark seen')
              .on('click', function(e) {
                e.stopPropagation();
                e.preventDefault();

                _row.trigger('yes' == String(_data.read) ? 'mark-unseen' : 'mark-seen');

                _context.close();

              }));

            if ('junkmail' == _data.folder) {
              /**
               * possibly this could be dynamic, but is current specific to SME Server (https://contribs.org)
               * which has a Learn feature (https://wiki.koozali.org/Learn)
               */
              _context.append(
                $('<a href="#" class="d-none"></a>')
                .on('click', function(e) {
                  e.stopPropagation();
                  e.preventDefault();

                  let _me = $(this);
                  let _data = _me.data();
                  // console.log( _data);

                  _row.trigger('execute-action', {
                    action: 'copy-message',
                    targetFolder: _data.folder

                  });

                  _context.close();

                })
                .on('check-availability', function(e) {
                  let _me = $(this);
                  featureLearnAsHam().then(d => {
                    if (d.available) {
                      // console.log( _me, d);
                      _me
                        .html('<i class="bi bi-shield-check text-success"></i>Learn as NOT Spam')
                        .data('folder', d.folder)
                        .removeClass('d-none');

                    }

                  });

                })
                .trigger('check-availability')

              );

            } else if (/^inbox$/i.test(_data.folder)) {
              /**
               * possibly this could be dynamic, but is current specific to SME Server (https://contribs.org)
               * which has a Learn feature (https://wiki.koozali.org/Learn)
               */
              _context.append(
                $('<a href="#" class="d-none"></a>')
                .on('click', function(e) {
                  e.stopPropagation();
                  e.preventDefault();

                  let _me = $(this);
                  let _data = _me.data();
                  // console.log( _data);

                  _row.trigger('execute-action', {
                    action: 'copy-message',
                    targetFolder: _data.folder

                  });

                  _context.close();

                })
                .on('check-availability', function(e) {
                  let _me = $(this);
                  featureLearnAsSpam().then(d => {
                    if (d.available) {
                      // console.log( _me, d);
                      _me
                        .html('<i class="bi bi-shield-check text-danger"></i>Learn as Spam')
                        .data('folder', d.folder)
                        .removeClass('d-none');

                    }

                  });

                })
                .trigger('check-availability')

              );

            } else {
              console.log('folder', _data.folder);

            }

            if (!!defaultFolders) {
              if (_data.folder == defaultFolders.Trash || _data.folder == 'junkmail') {
                _context.append($('<a href="#"><i class="bi bi-trash"></i>delete</a>').on('click', function(e) {
                  e.stopPropagation();
                  e.preventDefault();

                  _row.trigger('delete');

                  _context.close();

                }));

              } else {
                _context.append($('<a href="#"><i class="bi bi-trash"></i>move to ' + defaultFolders.Trash + '</a>').on('click', function(e) {
                  e.stopPropagation();
                  e.preventDefault();

                  _row.trigger('delete');

                  _context.close();

                }));

              }

            }

            /**
             * trigger back to the local system passing item and context menu,
             * the local system can add some contexts ..
             */
            $(document).trigger('mail-messages-context', {
              element: this,
              context: _context

            });

            _context.open(e);

          })
          .on('delete', function(e) {
            let _row = $(this);
            let _data = _row.data();
            let defaultFolders = $(document).data('default_folders');

            if (!!defaultFolders) {
              _row.trigger('set-next').trigger('set-previous');
              if (_data.folder == defaultFolders.Trash || _data.folder == 'junkmail') {
                _row.trigger('execute-action', {
                  action: 'delete-message'

                });

              } else {
                _row.trigger('execute-action', {
                  action: 'move-message',
                  targetFolder: defaultFolders.Trash

                });

              }

            }

          })
          .attr('draggable', true)
          .on('dragstart', function(e) {
            // console.log( 'handle drag start');
            e.originalEvent.dataTransfer.setData("text/plain", e.target.id);
            e.originalEvent.dataTransfer.effectAllowed = "move";
            //~ e.dataTransfer.setData("text", e.target.id);
          })
          .on('execute-action', function(e, params) {
            let _me = $(this);
            let _data = _me.data();

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
            data.uid = _data.message.uid;
            // console.log( params, _data);
            data.targetFolder = params.folder;

            $.extend(data, params);

            // console.log( _data);

            $('[selector]', _me).addClass('d-none');
            $('[from]', _me).append('<i class="spinner-grow spinner-grow-sm float-right"></i>');

            _me.addClass('font-italic');

            // console.log( data);	// data from the form
            _.post({
              url: _.url('<?= $this->route ?>'),
              data: data, //

            }).then(d => {
              if ('ack' == d.response) {

                /**
                 * actions could be:
                 *  => copy-message
                 *  => delete-message
                 *  => move-message
                 *  */
                // console.log( data.action);
                if ('copy-message' == data.action) {
                  $('[from] > i.spinner-grow', _me).remove();
                  $('[selector]', _me)
                    .prop('checked', false)
                    .removeClass('d-none');
                  _me.removeClass('font-italic');

                } else {
                  _me.remove();
                  // console.log( 'removed message ..', _me[0]);
                  // return;

                  if (_data.message.uid == $('#<?= $uidViewer ?>').data('uid')) {
                    $('#<?= $uidViewer ?>').trigger('clear');
                    $(document).trigger('mail-view-message-list');

                  }

                }

              } else {
                _.growl(d);

              }

              if ('no' != _data.refresh) $(document).trigger('mail-messages-refresh');

              $('#<?= $uidMsgs ?>').trigger('expose-bulk-controls');

            });

          })
          .on('mark-seen', function(e) {
            let _me = $(this);
            let _data = _me.data();

            let frm = $('#<?= $uidFrm ?>');
            let data = frm.serializeFormJSON();
            data.folder = _data.folder;
            data.uid = _data.message.uid;
            data.action = 'mark-seen';


            _.post({
              url: _.url('<?= $this->route ?>'),
              data: data,

            }).then(function(d) {
              if ('ack' == d.response) {
                $('[unseen]', _me).addClass('d-none');
                _me.data('read', 'yes');

              } else {
                _.growl(d);

              }

            });

          })
          .on('mark-unseen', function(e) {
            let _me = $(this);
            let _data = _me.data();

            let frm = $('#<?= $uidFrm ?>');
            let data = frm.serializeFormJSON();
            data.folder = _data.folder;
            data.uid = _data.message.uid;
            data.action = 'mark-unseen';


            _.post({
              url: _.url('<?= $this->route ?>'),
              data: data,

            }).then(function(d) {
              if ('ack' == d.response) {
                $('[unseen]', _me).removeClass('d-none');
                _me.data('read', 'no');

              } else {
                _.growl(d);

              }

            });

          });

        $('[trash-control]', row).on('click', function(e) {
          e.stopPropagation();
          e.preventDefault();
          row.trigger('delete');

        });

        row.swipeOn({
          left: function(e) {
            (function(el) {
              el.addClass('d-none');
              el.siblings('[message-control]').removeClass('col-10');

            })($('[trash-control]', this));

            // console.log( 'trash-off', e, this);

          },
          right: function(e) {
            (function(el) {
              el.removeClass('d-none');
              el.siblings('[message-control]').addClass('col-10');

            })($('[trash-control]', this));

            this.siblings().each(function(i, el) {
              (function(el) {
                el.addClass('d-none');
                el.siblings('[message-control]').removeClass('col-10');

              })($('[trash-control]', el));

            });

          }

        })

      });

      // remove any message not seen in latest round
      $('> [uid]', '#<?= $uidMsgs ?>').each((i, el) => {
        let _el = $(el);
        if (!_el.data('seen')) _el.remove();

      });

      if (!cacheData) $(document).trigger('mail-message-list-complete');

    };

    let mailInfo = folder => {
      let frm = $('#<?= $uidFrm ?>');
      let data = frm.serializeFormJSON();

      data.action = 'get-info';
      data.folder = folder;

      let pageSize = localStorage.getItem('mail-pageSize');
      if (!!pageSize) data.pageSize = pageSize;

      return _.post({
        url: _.url('<?= $this->route ?>'),
        data: data,

      });

    };

    let mailStatus = folder => {
      let frm = $('#<?= $uidFrm ?>');
      let data = frm.serializeFormJSON();

      data.action = 'get-status';
      data.folder = folder;

      let pageSize = localStorage.getItem('mail-pageSize');
      if (!!pageSize) data.pageSize = pageSize;

      return _.post({
        url: _.url('<?= $this->route ?>'),
        data: data,

      });

    };

    $(document)
      .on('mail-messages-refresh', (e) => {
        let data = $(document).data('mail-messages-data');
        if (!!data) {
          $(document).trigger('mail-messages-loader', data);

        } else {
          $(document).trigger('mail-messages', data);

        }

      })
      .on('mail-messages-loader', (e, data) => {

        $(document).trigger('mail-clear-reloader');
        if (_.isWindowHidden()) {
          console.log('defer refresh, not visible')
          $(document).data('mail-messages-reloader', window.setTimeout(() => $(document).trigger('mail-messages-loader', data), 10000));

        } else {
          $('i.bi-arrow-repeat', '#<?= $uidMsgs ?>').addClass('bi-spin');

          $(document).data('mail-messages-data', data);
          _.post({
            url: _.url('<?= $this->route ?>'),
            data: data, // data from the form

          }).then(d => {
            if ('ack' == d.response) {
              // console.log( data.key);
              sessionStorage.setItem(data.key, JSON.stringify(d.messages));
              // console.log( 'messages - ' + data.folder);
              // console.log( $('#<?= $uidMsgs ?>').data('folder'));
              let fldrs = {
                data: '',
                current: ''

              };

              if (!/search/.test($('#<?= $uidMsgs ?>').data('controlstate'))) {
                if (!!data.folder) fldrs.data = data.folder;
                if (!!$('#<?= $uidMsgs ?>').data('folder')) fldrs.current = $('#<?= $uidMsgs ?>').data('folder');

                if (fldrs.data == fldrs.current) {
                  if (data.page == Number($('input[name="page"]', '#<?= $uidFrm ?>').val())) {
                    _list_messages(d.messages, false, true);

                    if (0 == data.page) {
                      $(document).trigger('mail-clear-reloader');
                      $(document).data('mail-messages-reloader', window.setTimeout(() => {
                        sessionStorage.removeItem(data.key);
                        $(document).trigger('mail-messages-loader', data);

                      }, 20000));

                    }

                  }

                }

              }

            } else {
              _.growl(d);

            }
            $('i.bi-arrow-repeat', '#<?= $uidMsgs ?>')
              .removeClass('bi-spin');

          });

        }

      })
      .on('mail-messages-flush', e => {
        let frm = $('#<?= $uidFrm ?>');
        let data = frm.serializeFormJSON();
        let key = '<?= $this->route ?>-lastmessages-';

        if (!!data.folder) {
          if ('inbox' != String(data.folder).toLowerCase()) {
            key += data.folder + '-';

          }

        }

        let lastMessages = sessionStorage.getItem(key);
        if (!!lastMessages) {
          sessionStorage.removeItem(key);

        }

        $(document).trigger('mail-messages');

      })
      .on('mail-messages', (e, folder) => { // header view set here

        $(document).trigger('mail-clear-reloader');

        let frm = $('#<?= $uidFrm ?>');
        let data = frm.serializeFormJSON();

        data.action = 'get-messages';
        if (!!folder) {
          data.folder = folder;
        }
        // console.log( folder, data);

        let page = Number($('input[name="page"]', '#<?= $uidFrm ?>').val());
        let heading = $('<div class="row bg-light text-muted"></div>');
        ((col) => { // header view set here
          let primary = $('<div class="d-flex"></div>').appendTo(col);
          let bulkControl = $('<div class="py-1 input-group d-none text-right"><div class="mr-auto" status></div></div>').appendTo(col);
          let search = $('<div class="py-1 d-none"></div>').appendTo(col);
          let location = 'undefined' == typeof data.folder ? 'messages' : data.folder;

          let h = $('<h6 class="text-truncate pt-1 mb-1"></h6>').html(location).appendTo(primary);

          $('<button type="button" class="btn btn-sm ml-auto"><i class="bi bi-search"></i></button>')
            .attr('title', _.browser.isMobileDevice ? 'search' : 'ctrl+click for advanced search')
            .appendTo(primary)
            .on('click', function(e) {
              if (e.ctrlKey) {

                // console.log( 'ctrlKey');

                $(document)
                  .data('view', 'search')
                  .trigger('mail-set-view');

              } else {
                $('#<?= $uidMsgs ?>').trigger('expose-search-controls');

                $(document).trigger('mail-clear-reloader');
                $('input[type="search"]', search).focus();

              }

            });

          $('<button type="button" class="btn btn-sm"><i class="bi bi-chevron-left" title="previous page"></i></button>')
            .appendTo(primary)
            .on('click', function(e) {
              let v = Number($('input[name="page"]', '#<?= $uidFrm ?>').val());
              if (v > 0) {
                v--;
                $('input[name="page"]', '#<?= $uidFrm ?>').val(v);

                if (!!folder)
                  $(document).trigger('mail-messages', folder);
                else
                  $(document).trigger('mail-messages');

              }

            });

          if (page > -1) {
            let statDiv = $('<div class="small pt-1"></div>').appendTo(primary);

            $('<span></span>')
              .html(page + 1)
              .appendTo(statDiv);

            $('<span></span>')
              .appendTo(statDiv)
              .on('update', function(e) {
                // $(this).trigger('update-info');
                $(this).trigger('update-status'); // faster on bigger mailboxes

              })
              .on('update-info', function(e) {
                let _me = $(this);

                mailInfo(!!folder ? folder : 'default')
                  .then(d => {
                    if ('ack' == d.response) {
                      _me.html('/' + d.data.pages);

                      statDiv
                        .addClass('pointer')
                        .on('click', function(e) {
                          e.stopPropagation();
                          e.preventDefault();

                          let input = $('<input type="text" class="form-control text-center">');
                          input
                            .val(page + 1)
                            .on('goto', function(e) {
                              let _me = $(this);
                              let v = Number(_me.val());
                              if (v > 0) {

                                v--;
                                $('input[name="page"]', '#<?= $uidFrm ?>').val(v);

                                if (!!folder)
                                  $(document).trigger('mail-messages', folder);
                                else
                                  $(document).trigger('mail-messages');

                              }

                            })
                            .on('keyup', function(e) {
                              if (13 == e.keyCode) {
                                // console.log( 'enter');
                                e.stopPropagation();
                                e.preventDefault();

                                let _me = $(this);
                                _me.trigger('goto');

                              } else if (27 == e.keyCode) {
                                // console.log( 'enter');
                                e.stopPropagation();
                                e.preventDefault();

                                let _me = $(this);
                                _me.addClass('d-none');
                                statDiv.removeClass('d-none');
                                _me.remove();

                              }

                            });

                          if (_.browser.isMobileDevice) {
                            input
                              .attr('inputmode', 'numeric')
                              .attr('pattern', '[0-9]*')
                              .on('blur', function(e) {
                                let _me = $(this);
                                _me.trigger('goto');

                              });

                          }

                          statDiv.addClass('d-none');
                          input.insertAfter(statDiv[0]);
                          input.focus();

                        });

                    }

                  });

              })
              .on('update-status', function(e) {
                let _me = $(this);

                mailStatus(!!folder ? folder : 'default')
                  .then(d => {
                    if ('ack' == d.response) {
                      // console.log( d.data);

                      _me.html('/' + d.data.pages);
                      let txt = d.data.folder + '(' + d.data.messages + ')';
                      h.html(txt).attr('title', txt);

                      statDiv
                        .addClass('pointer')
                        .on('click', function(e) {
                          e.stopPropagation();
                          e.preventDefault();

                          let input = $('<input type="text" class="form-control text-center">');
                          input
                            .val(page + 1)
                            .on('goto', function(e) {
                              let _me = $(this);
                              let v = Number(_me.val());
                              if (v > 0) {

                                v--;
                                $('input[name="page"]', '#<?= $uidFrm ?>').val(v);

                                if (!!folder)
                                  $(document).trigger('mail-messages', folder);
                                else
                                  $(document).trigger('mail-messages');

                              }

                            })
                            .on('keyup', function(e) {
                              if (13 == e.keyCode) {
                                // console.log( 'enter');
                                e.stopPropagation();
                                e.preventDefault();

                                let _me = $(this);
                                _me.trigger('goto');

                              } else if (27 == e.keyCode) {
                                // console.log( 'enter');
                                e.stopPropagation();
                                e.preventDefault();

                                let _me = $(this);
                                _me.addClass('d-none');
                                statDiv.removeClass('d-none');
                                _me.remove();

                              }

                            });

                          if (_.browser.isMobileDevice) {
                            input
                              .attr('inputmode', 'numeric')
                              .attr('pattern', '[0-9]*')
                              .on('blur', function(e) {
                                let _me = $(this);
                                _me.trigger('goto');

                              });

                          }

                          statDiv.addClass('d-none');
                          input.insertAfter(statDiv[0]);
                          input.focus();

                        });

                    }

                  });

              })
              .trigger('update');

          }

          $('<button type="button" class="btn btn-sm"><i class="bi bi-chevron-right" title="next page"></i></button>')
            .appendTo(primary)
            .on('click', (e) => {
              let _page = $('input[name="page"]', '#<?= $uidFrm ?>');
              let v = Number(_page.val());
              v++;
              // console.log( v);
              _page.val(v);

              if (!!folder) {
                $(document).trigger('mail-messages', folder);

              } else {
                $(document).trigger('mail-messages');

              }
              // console.log( _page.val());

            });

          $('<button type="button" class="btn btn-sm pr-0"><i class="bi bi-arrow-repeat"></i></button>')
            .appendTo(primary)
            .on('click', function(e) {
              if (!!folder)
                $(document).trigger('mail-messages', folder);
              else
                $(document).trigger('mail-messages');

            });

          let _search_ig = $('<div class="input-group"></div>').appendTo(search);
          let _uid = 'uid' + parseInt(Math.random() * 1000000);
          let searchBody = $('<input type="checkbox" class="custom-control-input" id="' + _uid + '">');
          $('<div class="custom-control custom-switch"></div>')
            .appendTo(search)
            .append(searchBody)
            .append('<label class="custom-control-label" for="' + _uid + '">search email body</label>');

          let fldSearch = $('<input class="form-control" type="search">')
            .appendTo(_search_ig)
            .attr('placeholder', 'search ' + location)
            .attr('title', 'press escape to exit')
            .on('keyup', function(e) {
              if (13 == e.keyCode) {
                // console.log( 'enter');
                e.stopPropagation();
                e.preventDefault();

                let _me = $(this);
                if ('' != _me.val()) {
                  _me.trigger('search');

                }

              } else if (27 == e.keyCode) { // esc
                $('#<?= $uidMsgs ?>').trigger('expose-primary-controls');

              }

            })
            .on('search', function(e) {
              let frm = $('#<?= $uidFrm ?>');
              let data = frm.serializeFormJSON();

              data.action = 'search-messages';
              data['search-body'] = searchBody.prop('checked') ? 'yes' : 'no';
              data.term = String(fldSearch.val());
              if ('' == data.term.trim()) return;

              $('button[search-activate]', search).html('<i class="bi bi-arrow-repeat bi-spin"></i>').prop('disabled', true);
              fldSearch.prop('disabled', true);

              if (!!folder) {
                data.folder = folder;
              }

              $('> [uid]', '#<?= $uidMsgs ?>').remove();

              // DONE : Submit search
              // console.log( data);
              _.post({
                url: _.url('<?= $this->route ?>'),
                data: data, // data from the form

              }).then(d => {
                // console.log( d);

                if ('ack' == d.response) {
                  // DONE : Clear message list before loading search results
                  let heading = $('<div class="row bg-light text-muted"></div>');
                  let col = $('<div class="col"></div>').appendTo(heading);
                  let close = $('<i class="bi bi-x float-right pointer"></i>');
                  close.on('click', e => $(document).trigger('mail-messages', folder));

                  let h = $('<h6 class="text-truncate pt-1"></h6>')
                    .html(data.term)
                    .prepend(close)
                    .appendTo(col);

                  $('#<?= $uidMsgs ?>').html('').append(heading);
                  _list_messages(d.messages, false, false);

                } else {
                  _.growl(d);
                  $('i.bi-arrow-repeat', '#<?= $uidMsgs ?>').removeClass('bi-spin');

                }

              });

            });

          let iga = $('<div class="input-group-append"></div>').appendTo(_search_ig);
          $('<button type="button" class="btn btn-outline-secondary px-2" search-activate><i class="bi bi-arrow-return-left"></i></button>')
            .on('click', function(e) {
              fldSearch.trigger('search');
            })
            .appendTo(iga);

          iga = $('<div class="input-group-append"></div>').appendTo(_search_ig);
          $('<button type="button" class="btn btn-outline-secondary px-2" title="advanced search">A</button>')
            .on('click', function(e) {
              $('input[name="term"]', '#<?= $uidSearchAll ?>').val(fldSearch.val());

              $(document)
                .data('view', 'search')
                .trigger('mail-set-view');

            })
            .appendTo(iga);

          // not activating this feature
          $('<button type="button" class="btn btn-sm d-none" title="Learn as Ham"><i class="bi bi-shield-check text-success"></i></button>') // move to learnasspam
            .appendTo(bulkControl)
            .on('click', function(e) {
              e.stopPropagation();
              e.preventDefault();
              let _me = $(this);
              let _btn_data = _me.data();

              $('input[selector]:checked', '#<?= $uidMsgs ?>').each((i, ctrl) => {
                let _ctrl = $(ctrl)
                let _data = _ctrl.data();

                $('#' + _data.rowid)
                  .data('refresh', 'no')
                  .trigger('execute-action', {
                    action: 'copy-message',
                    targetFolder: _btn_data.folder

                  });

              });

            })
            .on('verify-feature-available', function(e) {
              let _me = $(this);
              featureLearnAsHam().then(d => {
                if (d.available) {
                  // console.log( d);
                  _me
                    .data('folder', d.folder.fullname)
                    .removeClass('d-none');

                }

              });

            }); // .trigger('verify-feature-available');
          // feature not activated

          let btnLearnAsSpam = $('<button type="button" class="btn btn-sm d-none" title="Learn as Spam"><i class="bi bi-shield-exclamation"></i></button>');

          btnLearnAsSpam
            .appendTo(bulkControl)
            .on('click', function(e) {
              e.stopPropagation();
              e.preventDefault();
              let _me = $(this);
              let _btn_data = _me.data();

              $('input[selector]:checked', '#<?= $uidMsgs ?>').each((i, ctrl) => {
                let _ctrl = $(ctrl)
                let _data = _ctrl.data();

                $('#' + _data.rowid)
                  .data('refresh', 'no')
                  .trigger('execute-action', {
                    action: 'move-message',
                    targetFolder: _btn_data.folder

                  });

              });

            })
            .on('verify-feature-available', function(e) {
              let _me = $(this);

              _me.addClass('d-none');
              featureLearnAsSpam().then(d => {
                if (d.available) {
                  // console.log( d);
                  _me
                    .data('folder', d.folder)
                    .removeClass('d-none');

                }

              });

            });

          $('<button type="button" class="btn btn-sm" title="Move to Trash"><i class="bi bi-trash"></i></button>') // delete all selected
            .appendTo(bulkControl)
            .on('click', function(e) {
              e.stopPropagation();
              e.preventDefault();

              $('input[selector]:checked', '#<?= $uidMsgs ?>').each((i, ctrl) => {
                let _ctrl = $(ctrl)
                let _data = _ctrl.data();

                $('#' + _data.rowid)
                  .data('refresh', 'no')
                  .trigger('delete');

              });

            });

          $('#<?= $uidMsgs ?>')
            .off('expose-bulk-controls')
            .off('expose-primary-controls')
            .off('expose-search-controls')
            .on('expose-bulk-controls', function() {

              let _me = $(this);
              let selectors = $('input[selector]:checked', this);

              if (selectors.length > 0) {
                $('.folderlist', '#<?= $uidFolders ?>').removeClass('searching');
                search.addClass('d-none');
                primary.removeClass('d-flex').addClass('d-none');

                $('[status]', bulkControl).html(selectors.length + 'msg/s');
                bulkControl.removeClass('d-none').addClass('d-flex');
                _me.data('controlstate', 'bulk');

                btnLearnAsSpam.trigger('verify-feature-available');

              } else {
                _me.trigger('expose-primary-controls');

              }

            })
            .on('expose-primary-controls', function() {

              let _me = $(this);

              $('.folderlist', '#<?= $uidFolders ?>').removeClass('searching');
              search.addClass('d-none');
              bulkControl.removeClass('d-flex').addClass('d-none');

              primary.removeClass('d-none').addClass('d-flex');
              _me.data('controlstate', 'primary');

            })
            .on('expose-search-controls', function() {
              let _me = $(this);

              $('.folderlist', '#<?= $uidFolders ?>').addClass('searching');

              $('input[searchThisFolder]', '#<?= $uidFolders ?>').prop('checked', false);
              if (!!folder) {
                $('input[searchThisFolder][data-folder="' + folder + '"]', '#<?= $uidFolders ?>').prop('checked', true);

              } else {
                $('input[searchThisFolder][data-folder="INBOX"]', '#<?= $uidFolders ?>').prop('checked', true);

              }

              primary.removeClass('d-flex').addClass('d-none');
              bulkControl.removeClass('d-flex').addClass('d-none');

              search.removeClass('d-none');
              _me.data('controlstate', 'search');

            });

        })($('<div class="col"></div>').appendTo(heading));

        $('#<?= $uidMsgs ?>')
          .html('')
          .append(heading)
          .trigger('expose-primary-controls');

        data.key = '<?= $this->route ?>-lastmessages-';
        if ('undefined' != typeof data.folder) {
          if ('inbox' != String(data.folder).toLowerCase()) {
            data.key += data.folder + '-';

          }

        }

        if (page > 0) data.key += page;

        let lastMessages = sessionStorage.getItem(data.key);
        // console.log( data.key, lastMessages);
        $('#<?= $uidMsgs ?>').data('folder', folder);
        if (!!lastMessages) {
          // console.log( 'lastMessages - ' + data.folder);
          try {
            _list_messages(JSON.parse(lastMessages), true, true);

          } catch (error) {
            console.log(error);

          }
          sessionStorage.removeItem(data.key);

        }

        let pageSize = localStorage.getItem('mail-pageSize');
        if (!!pageSize) data.pageSize = pageSize;

        $(document).trigger('mail-messages-loader', data);

      });

    $('#<?= $uidSearchAll ?>_form')
      .on('submit', function(e) {
        let _form = $(this);
        let _data = _form.serializeFormJSON();

        if ('' == String(_data.term).trim()) return; // this won't happen, but it here anyway ...

        /**--- ---[ search-all ]--- ---*/
        let gForm = $('#<?= $uidFrm ?>');
        let gData = _.extend(gForm.serializeFormJSON(), _data);

        $('button', _form)
          .html('')
          .append('<i class="spinner-grow spinner-grow-sm"></i>')
          .prop('disabled', true);

        $('input[type="search"], input[type="data"]', _form).prop('disabled', true);

        let heading = $('<div class="row bg-light text-muted"></div>');
        let col = $('<div class="col"></div>').appendTo(heading);
        let close = $('<i class="bi bi-x float-right pointer"></i>');
        close.on('click', function(e) {
          e.stopPropagation();
          e.preventDefault();
          $(document).trigger('mail-default-view');

        });

        let h = $('<h6 class="text-truncate pt-1"></h6>')
          .html(_data.term)
          .prepend(close)
          .insertBefore('#<?= $uidSearchAll ?>_form');

        // /**
        //  * button to return us to the default mail view
        //  * */
        // $('<button type="button" class="btn btn-light float-right pr-0" style="margin-top: -1rem;">&times;</button>')
        // 	.on( 'click', function( e) {
        // 		$(document).trigger( 'mail-default-view');

        // 	})
        // 	.insertBefore( h);


        $('#<?= $uidSearchAll ?>_form').addClass('d-none');
        $('#<?= $uidSearchAll ?>_buttons').addClass('d-none');

        let dataQ = [];

        $('input[type="checkbox"]', '#<?= $uidSearchAll ?>_folders').each((i, el) => {
          let _el = $(el);
          if (!_el.prop('checked')) {
            _el.closest('div.form-check').remove();
            return;

          }

          let data = _.extend(_el.data(), gData);
          data.action = 'search-messages';

          // console.table( data);
          dataQ.push({
            data: data,
            el: _el

          });

        });

        if (dataQ.length > 0) {
          let _f = data => {
            return new Promise(resolve => {

              let spinner = $('<i class="spinner-grow spinner-grow-sm mr-2"></i>')
              spinner.insertAfter(data.el[0]);
              data.el.addClass('d-none');

              // console.table( data);
              // resolve();
              // return;

              _.post({
                  url: _.url('<?= $this->route ?>'),
                  data: data.data, // data from the form

                })
                .then(d => {
                  // console.table( d);
                  // // console.log( d);

                  if ('ack' == d.response) {
                    // 	$('#<?= $uidMsgs ?>').html('').append( heading);
                    let fc = data.el.closest('div.form-check');
                    let container = $('<div></div>');
                    container.insertAfter(fc);
                    $.each(d.messages, (i, msg) => container.append(_list_message_row(msg, false)));

                    spinner.remove();
                    let badge = $('<div class="badge badge-pill badge-secondary float-right"></div>').html(d.messages.length);

                    fc
                      .removeClass('form-check')
                      .prepend(badge);

                    resolve();

                  } else {
                    _.growl(d);

                  }

                });

            });

          };

          let looper = () => {
            // console.log( 'looper');
            // if ( dataQ.length > 0) console.log( dataQ.shift());
            if (dataQ.length > 0) _f(dataQ.shift()).then(looper);

          };

          looper();

        }

        return false; // don't reload page

      });

    $(document)
      .on('mail-set-view', e => {
        let view = $(document).data('view');
        let focus = $(document).data('focus');

        if ('search' == view) {
          // console.log( 'search view');
          $('#<?= $uidFolders ?>').attr('class', 'd-none h-100');
          $('#<?= $uidMsgs ?>').attr('class', 'd-none h-100');

          $('#<?= $uidSearchAll ?>').attr('class', 'col-md-5 h-100');
          $('#<?= $uidViewer ?>').attr('class', 'd-none d-md-block col-md-7 px-1');

          $('input[type="search"]', '#<?= $uidSearchAll ?>').focus();
          $('body').removeClass('hide-nav-bar');

          $(document).trigger('resize-main-content-wrapper');

        } else if ('condensed' == view) {
          $('#<?= $uidSearchAll ?>').attr('class', 'd-none');
          $('#<?= $uidFolders ?>').attr('class', 'd-none h-100');

          if ('message-view' == focus) {
            $('#<?= $uidMsgs ?>').attr('class', 'd-none d-md-block col-md-3 border border-top-0 border-light h-100');
            $('#<?= $uidViewer ?>').attr('class', 'col-md-9 px-1');
            $('body').addClass('hide-nav-bar');

          } else {
            // message-list
            $('#<?= $uidMsgs ?>').attr('class', 'col-md-3 border border-top-0 border-light h-100');
            $('#<?= $uidViewer ?>').attr('class', 'd-none d-md-block col-md-9 px-1');
            $('body').removeClass('hide-nav-bar');

            $(document).trigger('resize-main-content-wrapper');

          }

        } else if ('wide' == view) {
          $('#<?= $uidSearchAll ?>').attr('class', 'd-none');
          $('#<?= $uidFolders ?>').attr('class', 'd-none d-sm-block col-sm-3 col-md-2 h-100');

          if ('message-view' == focus) {
            $('#<?= $uidMsgs ?>').attr('class', 'd-none d-md-block col-md-3 border border-top-0 border-light h-100');
            $('#<?= $uidViewer ?>').attr('class', 'col-sm-9 col-md-7 px-1');
            $('body').addClass('hide-nav-bar');

          } else {
            // message-list
            $('#<?= $uidMsgs ?>').attr('class', 'col-sm-9 col-md-3 border border-top-0 border-light h-100');
            $('#<?= $uidViewer ?>').attr('class', 'd-none d-md-block col-md-7 px-1');
            $('body').removeClass('hide-nav-bar');

            $(document).trigger('resize-main-content-wrapper');

          }

        }

      })
      .on('mail-view-state', e => {
        console.table({
          view: $(document).data('view'),
          focus: $(document).data('focus'),
          folders: $('#<?= $uidFolders ?>').attr('class'),
          list: $('#<?= $uidMsgs ?>').attr('class'),
          viewer: $('#<?= $uidViewer ?>').attr('class')

        });

      })
      .on('mail-default-view', e => {
        let key = '<?= $this->route ?>-view';
        let view = sessionStorage.getItem(key);

        if (!view) view = 'wide';
        if (['condensed', 'wide'].indexOf(view) < 0) view = 'wide';

        $(document).data('view', view);
        $(document).trigger('mail-set-view');

      })
      .on('mail-toggle-view', e => {
        let view = $(document).data('view');
        let key = '<?= $this->route ?>-view';

        if ('condensed' == view) {
          view = 'wide';
          sessionStorage.setItem(key, view);
        } else if ('wide' == view) {
          view = 'condensed';
          sessionStorage.setItem(key, view);

        } else {
          view = sessionStorage.getItem(key);
          if (!view) view = 'wide';
          if (['condensed', 'wide'].indexOf(view) < 0) view = 'wide';

        }

        // console.log( key, $(document).data('view'), view);
        $(document).data('view', view);
        $(document).trigger('mail-set-view');

      });

    $(document).on('mail-view-message-list', e => {
      $(document).data('focus', 'message-list');
      $(document).trigger('mail-set-view');
      // console.log('mail-view-message-list');

    });

    $(document).on('mail-view-message-set-url', (e, url) => {
      if (_.browser.isMobileDevice) {
        if (!!history.state) {
          history.replaceState({
            view: 'message'
          }, 'message', url);

        } else {
          history.pushState({
            view: 'message'
          }, 'message', url);

        }

      }

    });

    window.onpopstate = e => {
      if (!!e.state) {
        if ('message' == e.state.view) {
          $(document).trigger('mail-view-message');

        }

      } else {
        $(document).trigger('mail-view-message-list');

      }

    };

    $(document)
      .on('mail-info', (e, func) => {
        if ('function' != typeof func) func = d => console.log('ack' == d.response ? d.data : d);

        mailInfo('default').then(func);

      })
      .on('mail-status', (e, func) => {
        if ('function' != typeof func) func = d => console.log('ack' == d.response ? d.data : d);

        mailStatus('default').then(func);

      })
      .on('mail-view-message', e => {
        $(document).data('focus', 'message-view');
        $(document).trigger('mail-set-view');

      })
      .on('mail-message-load-first', e => $('#<?= $uidMsgs ?> > div[uid]').first().trigger('view'))
      .on('mail-message-load-next', e => {
        let uid = $('#<?= $uidViewer ?>').data('next');
        if ('undefined' != typeof uid) {
          $('#<?= $uidViewer ?>').removeData('next').removeData('prev');
          // console.log( 'mail-message-load-next - nid', nid);
          $('> [uid="' + uid + '"]', '#<?= $uidMsgs ?>').trigger('view');

        } else {
          $(document).trigger('mail-message-load-first');

        }

      })
      .on('mail-message-load-prev', e => {
        let uid = $('#<?= $uidViewer ?>').data('prev');
        if ('undefined' != typeof uid) {
          $('#<?= $uidViewer ?>').removeData('next').removeData('prev');
          // console.log( 'nid', nid);
          $('> [uid="' + uid + '"]', '#<?= $uidMsgs ?>').trigger('view');

        } else {
          $(document).trigger('mail-message-load-first');

        }

      });

    $('#<?= $uidViewer ?>').on('clear', function(e) {
      $(this)
        .html('')
        .removeData('message')
        .removeData('uid')
        .append('<div class="text-center pt-4 mt-4" style="font-size: 8em;"><i class="bi bi-envelope"></i></div>');

      $(document).trigger('mail-view-message-set-url', _.url('<?= $this->route ?>'));

      if (!_.browser.isMobileDevice && 'yes' == $(document).data('autoloadnext')) {
        $(document).trigger('mail-message-load-next');

      }

    });

    if (!_.browser.isMobileDevice) {
      $(document).on('keydown', function(e) {
        /**
         *	arrow keys are only triggered by onkeydown, not onkeypress
         *
         *	keycodes are:
         *		left = 37
         *		up = 38
         *		right = 39
         *		down = 40
         *		delete = 46
         */

        // console.log( e);

        if (38 == e.keyCode) {

          if ($('body').hasClass('modal-open')) return;

          e.stopPropagation();
          $(document).trigger('mail-message-load-prev');

        } else if (40 == e.keyCode) {

          if ($('body').hasClass('modal-open')) return;

          e.stopPropagation();
          console.log(e.keyCode, 'mail-message-load-next');
          $(document).trigger('mail-message-load-next');

        } else if (39 == e.keyCode) {

          if ($('body').hasClass('modal-open')) return;

          e.stopPropagation();
          $('iframe', '#<?= $uidViewer ?>').focus();

        } else if (46 == e.keyCode) { // delete

          if ($('body').hasClass('modal-open')) return;

          e.stopPropagation();
          (function(data) {

            // console.log( data);
            // return;

            if ('undefined' != typeof data.uid) {
              $('> [uid="' + data.uid + '"]', '#<?= $uidMsgs ?>').first().trigger('delete');

            }

          })($('#<?= $uidViewer ?>').data());

        }

      });

    }

    $(document).on('mail-clear-viewer', e => $('#<?= $uidViewer ?>').trigger('clear'));

    $(document)
      .data('route', '<?= $this->route ?>')
      .data('autoloadnext', '<?= (currentUser::option('email-autoloadnext') == 'yes' ? 'yes' : 'no') ?>');

    $(document).ready(() => {

      $('div[data-role="content"], div[data-role="main-content-wrapper"]').removeClass('pt-0 pt-2 pt-3 pt-4 pb-0 pb-1 pb-2 pb-3 pb-4');
      $('html, body, div[data-role="main-content-wrapper"] > .row, div[data-role="main-content-wrapper"] > .row > .col').addClass('h-100');

      $(document)
        .trigger('mail-messages')
        .trigger('mail-folderlist')
        .trigger('mail-toggle-view')
        .trigger('mail-view-message-list')
        .trigger('mail-clear-viewer');

      if (!_.browser.isMobileDevice) $('#<?= $uidMsgs ?>').focus();

      $(document).trigger('mail-load-complete');

    });

    window.setMailPageSize = i => localStorage.setItem('mail-pageSize', i);
    window.setMailPageSize.clear = () => localStorage.removeItem('mail-pageSize');

    console.info('use setMailPageSize(50) to set the page size');

  })(_brayworth_);
</script>
