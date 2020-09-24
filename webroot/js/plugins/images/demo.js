/*
 * jQuery File Upload Demo
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 */

/* global $ */

$(function() {
  'use strict';
  // Initialize the jQuery File Upload widget:
  $('#fileupload').fileupload({
    url: baseUrl+'album/upload',
    headers : {
      'X-CSRF-Token': csrfToken
    },
    disableImageResize: false,
    imageForceResize: true,
    imageQuality: 0.7,
    async: true, 
  }).on('fileuploadsubmit', function (e, data) {
    console.log(data);
  })
  .on('fileuploadstop',function(e, data){
    // window.location.href=window.location.href;
    console.log(e);
    console.log(data);
  });
});