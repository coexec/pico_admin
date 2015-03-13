PicoAdmin = {

    /**
     * @var String
     */
    baseUrl : null,

    /**
     * @var String  Currently open post path (persisted via session, to keep a loaded page opened)
     */
    openPost: null,

    /**
     * @var Object  Labels for admin UI
     */
    labels: null,

    /**
     * @param   {String}    baseUrl             Installation base URL
     * @param   {String}    dataUrl             Data URL from request
     * @param   {String}    openPost
     * @param   {Object}    markupSet           RTE markup configuration
     * @param   {Object}    introLabels         Labels for intro buttons: next, previous, skip, done
     * @param   {String}    introTextEditor     Intro text explaining the editor
     * @param   {Object}    adminLabels         various labels for use in admin JavaScript
     */
    init: function(baseUrl, dataUrl, openPost, markupSet, introLabels, introTextEditor, adminLabels) {
        PicoAdmin.baseUrl = baseUrl;
        PicoAdmin.openPost= openPost;
        PicoAdmin.labels  = adminLabels;

        PicoAdmin.Rte.init(markupSet);                      // initialize the RTE itself
        PicoAdmin.Rte.initResizable();                      // make editor elements resize w/ sidepanel
        PicoAdmin.Rte.addIntroAttributes(introTextEditor);  // initialize guided tour
        PicoAdmin.Navigation.init();                        // init posts tree side panel
        PicoAdmin.Navigation.initResizable();               // make side panel resizable to east
        PicoAdmin.Controls.init(introLabels);               // install controls observers
        PicoAdmin.AssetsManager.init(dataUrl);              // AJAX load + inject assets manager into DOM
        PicoAdmin.AssetsManager.updateWidth();              // adapt assets manager to use left over client width
    },

    Rte: {
        miu: {
            markdownTitle: function(markItUp, char) {
                heading = '';
                n = $.trim(markItUp.selection||markItUp.placeHolder).length;
                for(i = 0; i < n; i++) {
                    heading += char;
                }
                return '\n'+heading;
            }
        },

        init: function(markupSet) {
            $('#markdown').markItUp({
                previewParser       : true,
                previewParserPath:  '~/sets/markdown/preview.php',
                onShiftEnter:		{keepDefault:false, openWith:'\n\n'},
                markupSet:          markupSet
            });

            if( PicoAdmin.openPost )
            PicoAdmin.Rte.loadPostFile(PicoAdmin.openPost);
        },

        addIntroAttributes: function(introTextEditor) {
            var editor = $('#markItUpMarkdown');
            editor.attr('data-intro', introTextEditor);
            editor.attr('data-step', 3);
            editor.attr('data-tooltipClass', 'widest');
        },

        loadPost: function(elementPost, fileUrl) {
            if(typeof fileUrl != "undefined") {
                $('.nav .post').removeClass('open');
                elementPost.addClass('open');

                PicoAdmin.Rte.loadPostFile(fileUrl);
            }
        },

        loadPostFile: function(fileUrl) {
            $.post('admin/open', {file: fileUrl}, function(data) {
                $('#markdowneditor').data('currentFile', fileUrl);

                var currentFilename = fileUrl.replace(document.location.protocol + "//" + document.location.host, "");
                currentFilename += currentFilename.substr(currentFilename.length - 1) == '/' ? 'index' : '';
                if( currentFilename.substr(currentFilename.length - 3) != '.md') {
                    currentFilename += '.md';
                }
                $('#currentfile-name').html(currentFilename);

                $('#markdown').val(data);
            });
        },

        /**
         * @returns {Number}    Caret offset in RTE textarea
         */
        getCaret: function() {
            var el = document.getElementById('markdown');

            if(el.selectionStart) {
                return el.selectionStart;
            } else if(document.selection) {
                el.focus();

                var r = document.selection.createRange();
                if(r == null) {
                    return 0;
                }

                var re = el.createTextRange(),
                    rc = re.duplicate();
                re.moveToBookmark(r.getBookmark());
                rc.setEndPoint('EndToStart', re);

                return rc.text.length;
            }
            return 0;
        },

        insertAtCaret: function(str) {
            var editor      = $('#markdown');
            var offsetCaret =  PicoAdmin.Rte.getCaret();

            var contentOld = editor.val();
            var contentNew = contentOld.substr(0, offsetCaret) + str + contentOld.substr(offsetCaret);

            editor.val(contentNew);
        },

        initResizable: function() {
            $('#main').resizable({
                handles     : 'e',
                resize      : function( event, ui ) {
                    PicoAdmin.AssetsManager.updateWidth();
                }
            });
        }
    },

    Navigation: {
        init: function() {
            var elNav = $('.nav');

            // View post in website
            elNav.on('click', '.view', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');
                var win = window.open(url, '_blank');
                win.focus();
            });

            // Rename folder
            elNav.on('click', '.rename', function(e) {
                e.preventDefault();
                var dataUrl = $(this).attr('data-url').replace(PicoAdmin.baseUrl, '');

                // Remove leading and trailing slash from path
                var filenameOriginal = dataUrl.substr(0, dataUrl.length - 1);
                filenameOriginal = filenameOriginal.substr(filenameOriginal.lastIndexOf("/") + 1);

                var nameNew = prompt(PicoAdmin.labels.promptRenameDirectory, filenameOriginal);

                if(nameNew) {
                    $.post('admin/rename', { file: dataUrl, renameTo: nameNew }, function(data) {
                        $('a.refresh')[0].click();
                    });
                }
            });

            // Open post in admin backend
            elNav.on('click', '.post', function(e) {
                e.preventDefault();
                var dataUrl = $(this).attr('data-url');
                PicoAdmin.Rte.loadPost($('a[data-url="' + dataUrl + '"]'), dataUrl);
            });

            // Open index-post by click on directory
            elNav.on('click', '.post-directory', function(e) {
                e.preventDefault();
                var dataUrl = $(this).attr('data-url') + 'index';
                PicoAdmin.Rte.loadPost($('a[data-url="' + dataUrl + '"]'), dataUrl);
            });

            // Delete post / delete a posts directory
            elNav.on('click', '.delete', function(e) {
                e.preventDefault();

                var li = $(this).parents('li');
                var isDirectory = li.attr('class').indexOf("directory") > -1;
                var fileUrl = $(this).attr('data-url');

                if( isDirectory ) {
                    var directoryName = fileUrl.substr(fileUrl.length -1) == '/' ? fileUrl.substr(0, fileUrl.length - 1) : fileUrl;
                    directoryName     = directoryName.indexOf("/") == -1 ? directoryName : directoryName.substr(directoryName.lastIndexOf("/") + 1);
                    if(!confirm( PicoAdmin.labels.confirmDeleteDirectory.replace('<directoryName>', directoryName) )) return false;

                    $.post('admin/deletedirectory', {file: fileUrl}, function(data) {
                        li.remove();
                    });
                } else {
                    var filename= fileUrl.substr(fileUrl.lastIndexOf("/") + 1) + ".md";
                    var postName= PicoAdmin.Navigation.getPostTitleByDataUrl(fileUrl);

                    if(!confirm( PicoAdmin.labels.confirmDeletePost.replace('<postName>', postName).replace('<filename>', filename) )) return false;

                    $('.nav .post').removeClass('open');

                    $.post('admin/remove', {file: fileUrl}, function(data) {
                        li.remove();
                        $('#markdowneditor').data('currentFile', '');
                        $('#markdown').val(data);
                    });
                }
            });

            PicoAdmin.Navigation.initCurrentFileLabel();
        },

        /**
         * Label w/ option to rename current post file
         */
        initCurrentFileLabel: function() {
            var elCurrentFile = $('#currentfile');

            elCurrentFile.on('click', function() {
                var filename = $('#currentfile-name').html();
                filename = filename.substr(filename.lastIndexOf('/') + 1);
                var nameNew = prompt(PicoAdmin.labels.promptRenameFile, filename);
                if( nameNew ) {
                    $.post('admin/rename', {file: filename, renameTo: nameNew}, function(data) {
                        //$('a.refresh')[0].click();
                    });
                }
            });

            elCurrentFile.on('mouseover', function() {
                $('#currentfile-rename-icon').show();
            });
            elCurrentFile.on('mouseout', function() {
                $('#currentfile-rename-icon').hide();
            });
        },

        getPostTitleByDataUrl: function(fileUrl) {
            var title = $('a[data-url="' + fileUrl + '"]')[0].innerHTML.split(">")[2].replace('"', '').trim();
            return title.indexOf("/") == -1 ? title : title.split("/")[1];
        },

        initResizable: function() {
            $('#sidebar').resizable({
                minWidth    : 250,
                handles     : 'e',
                resize      : function( event, ui ) {
                    $('#sidebar').find('.controls').css({'width': (ui.size.width - 20) + 'px' });
                    $('#markdowneditor').css({'margin-left': ( (ui.size.width + 10) + 'px')});
                    $('#currentfile').css({'left': ( (ui.size.width + 10) + 'px')});
                }
            });
        }
    },

    Controls: {
        init: function(introLabels) {
            // New
            $('.controls .new').on('click', function(e) {
                e.preventDefault();
                var title = prompt(PicoAdmin.labels.promptNewPost, '');
                if(title != null && title != '') {
                    $.post('admin/new', {title: title}, function(data) {
                        if(data.error) {
                            alert(data.error);
                        } else {
                            $('.nav .post').removeClass('open');
                            $('#markdowneditor').data('currentFile', data.file);
                            $('#markdown').val(data.content);
                            $('.nav').prepend('<li><a href="#" data-url="{{ base_url }}/' + data.file + '" class="post open"><span data-icon="3" aria-hidden="true"></span>' + data.title + '</a><a href="{{ base_url }}/' + data.file + '" target="_blank" class="view" title="View">5</a><a href="#" data-url="{{ base_url }}/' + data.file + '" class="delete" title="Delete">4</a></li>')
                        }
                    }, 'json');
                }
            });

            // Save
            $('.controls .save').on('click', function(e) {
                e.preventDefault();

                $('#saving').text(PicoAdmin.labels.saving + '...').addClass('active');
                $.post('admin/save', {
                    file   : $('#markdowneditor').data('currentFile'),
                    content: $('#markdown').val()
                }, function(data) {
                    $('#saving').text(PicoAdmin.labels.saved);
                   setTimeout(function() {
                        $('#saving').removeClass('active');
                    }, 1000);
                });
            });

            // Refresh
            $('.controls .refresh').on('click', function(e) {
                e.preventDefault();
                window.location.href = document.location.pathname;
            });

            // View currently opened post in website
            $('.controls .show').on('click', function(e) {
                e.preventDefault();
                $('a.post.open').parent().find('.view').click()
            });

            // Start introJs UI tour
            $('.controls .help').on('click', function(e) {
                e.preventDefault();
                var intro = introJs();
                intro.setOption('nextLabel', introLabels.nextLabel);
                intro.setOption('prevLabel', introLabels.prevLabel);
                intro.setOption('skipLabel', introLabels.skipLabel);
                intro.setOption('doneLabel', introLabels.doneLabel);
                intro.start();
            });
        }
    },

    AssetsManager: {

        fileUrl: null,

        /**
         * Render assets manager new, listing files at given path
         *
         * @param fileUrl
         */
        init: function(fileUrl) {
            PicoAdmin.AssetsManager.fileUrl = fileUrl;

            if(typeof  fileUrl == 'unknown') fileUrl = $(this).attr('data-url');
            $.ajax({
                   url    : "admin/assets",
                   type   : "POST",
                   data   : {file: fileUrl},
                   cache  : false,
                   success: function(result) {
                       $("#assets").html(result);

                       // Add observer: create directory button
                       $('#assetscreatedirectory').on('click', function() {
                           var directory = prompt(PicoAdmin.labels.directoryCreatePrompt, "");
                           if(directory) {
                               $.post('admin/mkdir', {
                                   path     : $('#uppath').val(),
                                   dirname  : directory
                               }, function(data) {
                                   PicoAdmin.AssetsManager.init( PicoAdmin.AssetsManager.fileUrl );
                               });
                           }
                       });
                   }
               });
        },

        /**
         * Show thumb of image, w/ infos about size of file and image resolution
         *
         * @param   {String}    pathImage
         * @param   {Number}    size
         * @param   {Number}    width
         * @param   {Number}    height
         * @param   {String}    title
         * @param   {String}    description
         */
        showThumb: function(pathImage, size, width, height, title, description) {
            var offsetFilename = pathImage.lastIndexOf("/");
            var path = pathImage.substr(0, offsetFilename);
            var filename = pathImage.substr(offsetFilename + 1);

            $('#assetimagepreview').append($('<img />').attr({
                 id   : "imgassetpreview",
                 src  : document.location.href.split('/admin')[0] + path + "/thumbs/" + filename,
                 width: 200
            }).add($('<div></div>').attr({
                  id: "imageassetpreviewinfos"
            }).append(
                  width + ' x '
                + height + ' px / '
                + size
                + '<br />' + PicoAdmin.labels.title + ': ' + title
                + '<br />' + PicoAdmin.labels.description + ': ' + description)));
        },

        hideThumb: function() {
            $('#imgassetpreview').remove();
            $('#imageassetpreviewinfos').remove();
        },

        deleteFile: function(pathFileFull, isFolder) {
            var filename = pathFileFull.substr(pathFileFull.lastIndexOf("/") + 1);
            if(confirm( (isFolder ? PicoAdmin.labels.confirmDeleteDirectory : PicoAdmin.labels.confirmDeleteFile).replace('<fileFolderName>', filename) )
            ) {
                $.post((isFolder ? 'admin/deletedirectory' : 'admin/remove'), {
                    file: pathFileFull
                }, function(data) {
                    // Reload assets manager
                    PicoAdmin.AssetsManager.init( PicoAdmin.AssetsManager.fileUrl );
                });
            }
        },

        /**
         * Rename asset file or directory
         *
         * @param   {String}    pathFileFull
         * @param   {Boolean}   isDirectory
         */
        rename: function(pathFileFull, isDirectory) {
            var filename = pathFileFull.substr(pathFileFull.lastIndexOf("/") + 1);
            var nameNew = prompt( (isDirectory ? PicoAdmin.labels.promptRenameDirectory : PicoAdmin.labels.promptRenameFile), filename);

            if( nameNew ) {
                $.post('admin/rename', { file: pathFileFull, renameTo: nameNew }, function(data) {
                    // Reload assets manager
                    PicoAdmin.AssetsManager.init( PicoAdmin.AssetsManager.fileUrl );
                });
            }
        },

        /**
         * Edit meta attributes (title and description) of image
         *
         * @param   {String}    pathFileFull
         * @param   {String}    titleOld
         * @param   {String}    descriptionOld
         */
        editMeta: function(pathFileFull, titleOld, descriptionOld) {
            var filename = pathFileFull.substr(pathFileFull.lastIndexOf("/") + 1);

            var titleNew        = prompt( PicoAdmin.labels.promptMetaTitle, titleOld);
            if( titleNew ) {
                $.post('admin/metatitle', { file: pathFileFull, title: titleNew }, function(data) { });
            }

            var descriptionNew  = prompt( PicoAdmin.labels.promptMetaDescription, descriptionOld);
            if( descriptionNew ) {
                $.post('admin/metadescription', { file: pathFileFull, description: descriptionNew }, function(data) { });
            }

            if( titleNew || descriptionNew ) {
                // Reload assets manager
                PicoAdmin.AssetsManager.init( PicoAdmin.AssetsManager.fileUrl );
            }
        },

        download: function(pathFile) {
            $("body").append("<iframe src='admin/download?file=" + pathFile + "' style='display: none;'></iframe>");
        },

        /**
         * Insert code for clicked image into current post in RTE
         *
         * @param   {String}  urlFile
         */
        insertIntoPage: function(urlFile) {
            var filename                 = urlFile.substr(urlFile.lastIndexOf("/") + 1);
            var filenameWithoutExtension = filename.substr(0, filename.lastIndexOf("."));
            PicoAdmin.Rte.insertAtCaret(
                '\n\n'
              + '![' + filenameWithoutExtension // Alt attribute
              + '](' + urlFile + ' "Some Text")'
            );
        },

        /**
         * Update width of assets manager panel
         */
        updateWidth: function() {
            $('div#assetsmanager').css({
                width: (document.body.clientWidth - $('#main').outerWidth()) + 'px'
            });
        }
    }
};