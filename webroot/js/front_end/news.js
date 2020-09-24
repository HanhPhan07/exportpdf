$( document ).ready(function() {
    
    var currentView = $("#view").val();

    if (currentView == "lists") {
        listsModule.initForm()
    }
    if (currentView == "index") {
        indexModule.initForm()
    }
   if (currentView == "edit") {
        editModule.initForm()
    }
});
var listsModule = (function() {
    var page = 1;
    var check = true;
    var initForm = function() {
        _initPagi();
        initEventListener();
    }
    var initEventListener = function() {
        $(document).on("click",  ".tab-common a", function(e) {  
            e.preventDefault();
            attr = $(this).attr('attr')
            $('#myTab'+attr+' .nav-link').removeClass('active');
            $(this).tab('show'); 
        });
        $(document).on("click",  ".delete-btn", function() { _delete($(this).attr('attr')); });
        $(document).on("click",  ".Show-btn", function() { _show($(this).attr('attr')); })
        $(document).on("click",  ".Hide-btn", function() { _hide($(this).attr('attr')); })
    }
    var _delete = function(id){
        swal({
            title: "Are you sure you want to delete this news?",
            icon: "error",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {
            var url = baseUrl + 'news/delete?id='+id
            $.ajax({
                method: 'GET',
                url: url
            })
            .done(function (response) {
                response = JSON.parse(response)
                if (response.code) {
                    swal("This news has been deleted!", {
                        icon: "success",
                    });
                    location.reload();
                }

            });
        })
    }
    var _show = function(id){
        swal({
            title: "Are you sure you want to show this news?",
            icon: "error",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {
            var url = baseUrl + 'news/ShowHide?id='+id+'&flag='+1
            $.ajax({
                method: 'GET',
                url: url
            })
            .done(function (response) {
                response = JSON.parse(response)
                console.log(response)
                if (response.code) {
                    swal("This news has been changed!", {
                        icon: "success",
                    });
                    console.log(page)
                    showPage(sessionStorage.getItem("page"))
                }

            });
        })
    }
    var _hide = function(id){
        swal({
            title: "Are you sure you want to hide this?",
            icon: "error",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {
            var url = baseUrl + 'news/ShowHide?id='+id+'&flag='+0
            $.ajax({
                method: 'GET',
                url: url
            })
            .done(function (response) {
                response = JSON.parse(response)
                console.log(response)
                if (response.code) {
                    swal("This news has been changed!", {
                        icon: "success",
                    });
                    console.log(page)
                    showPage(sessionStorage.getItem("page"))
                }

            });
        })
    }
    var _initPagi = function() {

        if ($('#tablePaging').length > 0) {
            loadPage();
        }
    }
    var loadPage = function () {
            window.tp = new Pagination('#tablePaging', {
                itemsCount: $('#count').val(),
                pageSize: $('#page-size').val(),
                pageRange: false,
                onPageSizeChange: function (ps) {
                    console.log('changed to ' + ps);
                },
                onPageChange: function (paging) {
                    console.log(paging.currentPage)
                    showPage(paging.currentPage);
                    page_show = paging.currentPage;
                    $('.page-range').hide();
                }
            });
        }
    var  showPage = function(page) {
        sessionStorage.setItem("page", page);
        var url = baseUrl + 'news/lists?page='+page;
         var params = $('#ApForm').serializeArray();
        $.ajax({
            method: 'POST',
            url: url,
            data: params
        })
        .done(function (msg) {
            $("#list-result").html('');
            $("#list-result").html(msg);
        });
    }
    return {
        initForm:initForm
    }
    


})()
var indexModule = (function() {
    var check =true;
    var initForm = function() {
        _initCk();
        $("#myTab a").click(function(e){
            e.preventDefault();
            $('.nav-link').removeClass('active')
            $(this).tab('show');
            console.log($(this))
        })
        initEventListener();
    }
     var initEventListener = function() {
        
        $(document).on("click",  ".submit-btn", function(e) {
            e.preventDefault();
            _validate();
         })
    }
    var _validate = function(id){
        check = true
        // checkExistEach('JPTitle');
        // checkExistEach('VNTitle');
        // checkExistEach('ENTitle');
        // if(CKEDITOR.instances.VNContents.document.getBody().getChild(0).getText() == '') check = false;
        // if(CKEDITOR.instances.JPContents.document.getBody().getChild(0).getText() == '') check = false;
        // if(CKEDITOR.instances.ENContents.document.getBody().getChild(0).getText() == '') check = false;
        if(($('input[name=Title]').val() !='' && CKEDITOR.instances.Contents.document.getBody().getChild(0).getText().trim() != '')
           // || ($('input[name=VNTitle]').val() !='' && CKEDITOR.instances.VNContents.document.getBody().getChild(0).getText().trim() != '')
           // || ($('input[name=ENTitle]').val() !='' && CKEDITOR.instances.ENContents.document.getBody().getChild(0).getText().trim()!= '')
            ){
             $('#ApForm').submit();
        }else{
             swal({
            title: "Please fill all fields",
            icon: "error",
           
            })
        }
    }
    var checkExistEach = function(input){
        if($('input[name='+input+']').val() ==''){        
            check = false;
        }
    }

    var _initCk = function() {
        
        CKEDITOR.replace( 'Contents', {extraPlugins: 'imageuploader',
            "language" : "jp"
        });
        
    }

    return {
        initForm:initForm
    }
})()
var editModule = (function() {
    var check = true
    var initForm = function() {
        _initCk();
        $("#myTab a").click(function(e){
            e.preventDefault();
            $('.nav-link').removeClass('active')
            $(this).tab('show');
        })
        initEventListener()
    }
    var initEventListener = function() {
        
        $(document).on("click",  ".submit-btn", function(e) {
            e.preventDefault();
            _validate();
         })
    }
    var checkExistEach = function(input){
        if($('input[name='+input+']').val() ==''){        
            check = false;
        }
    }
    var _validate = function(id){
        check = true
        // checkExistEach('JPTitle');
        // checkExistEach('VNTitle');
        // checkExistEach('ENTitle');
        // if(CKEDITOR.instances.VNContents.document.getBody().getChild(0).getText() == '') check = false;
        // if(CKEDITOR.instances.JPContents.document.getBody().getChild(0).getText() == '') check = false;
        // if(CKEDITOR.instances.ENContents.document.getBody().getChild(0).getText() == '') check = false;
        if(($('input[name=Title]').val() !='' && CKEDITOR.instances.Contents.document.getBody().getChild(0).getText().trim() != '')
            //|| ($('input[name=VNTitle]').val() !='' && CKEDITOR.instances.VNContents.document.getBody().getChild(0).getText().trim() != '')
           // || ($('input[name=ENTitle]').val() !='' && CKEDITOR.instances.ENContents.document.getBody().getChild(0).getText().trim()!= '')
           ){
             $('#ApForm').submit();
        }else{
             swal({
            title: "Please fill all fields",
            icon: "error",
           
            })
        }
    }
    var _initCk = function() {
        CKEDITOR.replace( 'Contents', {
            extraPlugins: 'imageuploader',
            "language" : "jp"
        });
      
    }

    return {
        initForm:initForm
    }
})()

