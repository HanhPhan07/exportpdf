jQuery(function(){    
    $('input[name=Background]').click(function(){
        if($(this).prop("checked") == true){
          $(this).val("1");
        }
        else if($(this).prop("checked") == false){
          $(this).val("0");
        }
    });
    // delete all photos from album
    jQuery(".delete-all").click(function(){
        var id = jQuery(this).attr('data-id');
        jQuery.ajax({
            type: 'post',
            url: __baseUrl + '/album/deleteAllPhoto',
            headers: {'X-CSRF-TOKEN': csrfToken},
            data:{'ID': id}
        }).done(function (response) {
            console.log(response);
            if(response.success){
                $("#gallery-photo").load(location.href + " #gallery-photo");
                swal("Success!", "Photos in this album have been removed!", "success")
                location.reload();
                return;
            }
        });
    });

    // download all photos from album
    jQuery(".download-all").click(function(){
        var id = jQuery(this).attr('data-id');
        jQuery.ajax({
            type: 'post',
            url: __baseUrl + 'album/downloadAllPhoto',
            headers: {'X-CSRF-TOKEN': csrfToken},
            data:{'ID': id}
        }).done(function (response) {
            console.log(response);
            if(response.success){
                window.location.href = response.File;
            }else{
                swal("Your photo can not be zipped to download!", {
                    buttons: {
                        cancel: "OK",
                    },
                });
            }
        })
        .fail(function(response){
            console.log(response);
        });
    });
});


$(document).ready(function() { 
    // window.onload = date_time('date_time'); 
    bindChangeDescriptionBackgroundEvent();  
});

function bindChangeDescriptionBackgroundEvent() {
    //This javascript file was called twice in album/photo page
    $(".change-album-background-btn").unbind('click');
    $(document).off('click', '.modal-image-block');
    $(document).off('click', '.save-description-background');
    $(document).off('click', '.change-description-background-position');
    $(document).off('click', '.accept-album-background-position-btn');
    $(document).off('click', '.denied-album-background-position-btn');


    $(".change-album-background-btn").on('click', function() {displayImagesInDialog(); })
    $(document).on('click', '.modal-image-block', function(){ setSelectedImage(this); })
    $(document).on('click', '.save-description-background', function(){ saveDescriptionBackground(); })
    $(document).on('click', '.change-description-background-position', function(){ changeDescriptionBackgroundPosition(); })

    $(document).on('click', '.accept-album-background-position-btn', function(){ saveAlbumBackgroundPosition(); })
    $(document).on('click', '.denied-album-background-position-btn', function(){ cancelAlbumBackgroundPosition(); })
}

function displayImagesInDialog() {
    var albumContainerSelector = $("#gallery-photo");
    var modelSelector = $("#imageListModal");
    var modalBody = modelSelector.find(".modal-body");
    var allImages = albumContainerSelector.find("img");

    //Reset modal
    modalBody.html("")

    if (allImages.length == 0) return;
    $.each(allImages, function(imageKey, imageElement) {
        modalBody.append(generateImageThumbnail($(imageElement).attr('src'), $(imageElement).data('imgid')))
    })
    modelSelector.modal('show')
}

function generateImageThumbnail(url, imgID) {
    if(!url || !imgID) return;

    return "<div class='modal-image-block' data-modal-img-id='"+imgID+"'><img src='"+url+"' /><div class='tick-overlay'></div></div>"
}

function setSelectedImage(selectedImageBlock) {
    var modelSelector = $("#imageListModal");
    var modalBody = modelSelector.find(".modal-body");
    var allImageBlocks = modalBody.find(".modal-image-block");
    allImageBlocks.removeClass('active');
    $(selectedImageBlock).addClass('active')
}

function saveDescriptionBackground() {
    var modelSelector = $("#imageListModal");
    var modalBody = modelSelector.find(".modal-body");
    var selectedBlock = modalBody.find(".modal-image-block.active");
    var imageID = selectedBlock.data('modal-img-id');
    var modelSelector = $("#imageListModal");
    var requestData = {
        'image_id': imageID,
        'album_id': $("input[name='AlbumID']").val()
    };

    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': csrfToken}
    });
    $.post( __baseUrl + "album/setDescriptionBackground", requestData, function(response) {
        swal(response.message)
        updateDescriptionBackground(imageID);
        modelSelector.modal('hide')
    })
    .fail(function() {
        swal( "Cannot update description background" );
    });
}

function updateDescriptionBackground(imageID) {
    var imageSrc = $('img[data-imgid="'+imageID+'"]').attr('src');
    var descriptionElement = $(".xmas").css('background-image', 'url("'+imageSrc+'")');
    $(".xmas").data('background-position-ori', "0px 0px")
    $(".xmas").css('background-position', "0px 0px")
}

function changeDescriptionBackgroundPosition() {
    var modelSelector = $("#imageListModal");
    var targetContainer = ".xmas";
    var containerContent = $(targetContainer).find('.xmas1');
    $.fn.dragableBackground(targetContainer);
    containerContent.css('visibility', 'hidden');
    modelSelector.modal('hide');
    $(".edit-album-background-position-container").show();
    $(".change-album-background-btn").hide();
}

function saveAlbumBackgroundPosition() {
    var backgroundContainer = $(".xmas");
    var position = backgroundContainer.data('bg-position');
    var requestData = {
        'album_id': $("input[name='AlbumID']").val(),
        'background_position': position
    }
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': csrfToken}
    });
    $.post( __baseUrl + "album/setDescriptionBackgroundPosition", requestData, function(response) {
        swal(response.message)
        backgroundContainer.data('background-position-ori', position)
        closeChangeAlbumBackgroundPositionState()
    })
    .fail(function() {
        swal( "Cannot update description background" );
    });
}

function cancelAlbumBackgroundPosition() {
    var backgroundContainer = $(".xmas");
    var originalPosition = backgroundContainer.data('background-position-ori');
    backgroundContainer.css('background-position', originalPosition)
    
    closeChangeAlbumBackgroundPositionState();
}

function closeChangeAlbumBackgroundPositionState() {
    var targetContainer = ".xmas";
    var containerContent = $(targetContainer).find('.xmas1');
    $.fn.dragableBackground.destroy();
    containerContent.css('visibility', 'visible');
    $(".edit-album-background-position-container").hide();
    $(".change-album-background-btn").show();
}

(function ($) {
 
    $.fn.dragableBackground = function(div) {
        var $bg = $(div),
        elbounds = {
            w: parseInt($bg.width()), 
            h: parseInt($bg.height())
        },
        bounds = {w: 2350 - elbounds.w, h: 1750 - elbounds.h},
        origin = {x: null, y: null},
        start = {x: null, y: null},
        movecontinue = false;

        //Set original position to background
        if(!origin.x) {
            var positionOrigin = $(div).data('background-position-ori');
            if (positionOrigin == "") {
                origin = {x: 0, y: 0};
                start = {x: 0, y: 0};
            } else {
                positionOrigin = positionOrigin.replace(/px/g, "");
                positionOrigin = positionOrigin.split(" ");
                
                origin = {x: positionOrigin[0], y: positionOrigin[1]};
                start = {x: positionOrigin[0], y: positionOrigin[1]};
            }
        }
    
        move = function(e) {
            var inbounds = {x: false, y: false},
                offset = {
                    x: start.x - (origin.x - e.clientX), 
                    y: start.y - (origin.y - e.clientY)
                };
            
            inbounds.x = offset.x < 0 && (offset.x * -1) < bounds.w;
            inbounds.y = offset.y < 0 && (offset.y * -1) < bounds.h;
            
            if (movecontinue && inbounds.x && inbounds.y) {
                start.x = offset.x;
                start.y = offset.y;
                //Remove comment to enable drag both x and y collision
                // $(this).css('background-position', +start.x +'px ' + start.y + 'px');
                $(this).css('background-position', '0px ' + start.y + 'px');
                $(this).data("bg-position", '0px ' + start.y + 'px');

            }
            
            origin.x = e.clientX;
            origin.y = e.clientY;
            
            e.stopPropagation();
            return false;
        }
        
        handle = function(e) {
            movecontinue = false;
            $bg.unbind('mousemove', move);
            
            if (e.type == 'mousedown') {
                origin.x = e.clientX;
                origin.y = e.clientY;
                movecontinue = true;
                $bg.bind('mousemove', move);
            } else {
                $(document.body).focus();
            }
            
            e.stopPropagation();
            return false;
        }

        $bg.bind('mousedown mouseup mouseleave', handle);

        $.fn.dragableBackground.destroy = function() {
            $bg.unbind('mousedown mouseup mouseleave', handle);
        }
    };

}(jQuery));

