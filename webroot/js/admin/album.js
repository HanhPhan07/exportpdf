 jQuery(document).ready(function() {
    $( '#listTeam' ).on( 'click', '.btn-edit-album', function () { 
            $("form button[type=submit]").html("Save");          
            $('#AlbumID').val($(this).attr('data-ID'));
            $('#AlbumName').val($(this).attr('data-Name'));
           	$("textarea#AlbumDescription").val($(this).attr('data-Description'));
    });
    $( '#listTeam' ).on( 'click', '.btn-delete-album', function () { 
        var id = $(this).attr('data-ID');
        jQuery.ajax({
            type: 'post',
            url: baseUrl + 'admin/album/deleteAllPhoto',
            headers: {'X-CSRF-TOKEN': csrfToken},
            data:{'ID': id}
        }).done(function (response) {
            console.log(response);
            if(response.success){
                $("#gallery-photo").load(location.href + " #gallery-photo");
                swal("Success!", "Photos in this album have been removed!", "success")
                window.location.href=window.location.href;
                return;
            }
        });
    });
})