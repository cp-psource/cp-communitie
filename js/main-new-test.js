/*
 * jQuery File Upload Plugin JS Example 7.0
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint nomen: true, unparam: true, regexp: true */
/*global $, window, document */

jQuery(function($) {
    'use strict';

    $('#fileupload').fileupload({
        url: '/server/php/',
        autoUpload: true,
        maxNumberOfFiles: 1,
        maxFileSize: 100000000,
        acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
        process: [{
                action: 'load',
                fileTypes: /^image\/(gif|jpeg|png)$/,
                maxFileSize: 100000000
            },
            {
                action: 'resize',
                maxWidth: 640,
                maxHeight: 640
            },
            {
                action: 'save'
            }
        ]
    });

    $.ajax({
        url: $('#fileupload').fileupload('option', 'url'),
        dataType: 'json',
        context: $('#fileupload')[0]
    }).done(function(result) {
        $(this).fileupload('option', 'done')
            .call(this, null, { result: result });
    });
});