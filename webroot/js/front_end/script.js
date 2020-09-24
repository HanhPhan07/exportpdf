jQuery(document).ready(function () {
    window.onload = date_time('date_time');
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
        $('.body-wrapper').toggleClass('side-menu-toggle');
        $('.content-mask').toggle();
    });

    if (utilsModule().isMobile()) {
        $(document).on("click", ".content-mask", function () {
            $('#sidebarCollapse').click();
        });
    }

    monthPickerModule.initial(['#input-al-date']);

    var exportDatePickers = ["#exportFromDate", "#exportToDate"];
    datepickerModule.initial(exportDatePickers, false);

    jQuery("#menu-export-pl").click(function (event) {
        event.preventDefault();
        jQuery("#popup-export-al-modal").modal();
    });

    jQuery("#menu-export-ul").click(function (event) {
        event.preventDefault();
        jQuery("#popup-export-al-modal").modal();
    });

    jQuery("#menu-export-ot").click(function (event) {
        event.preventDefault();
        jQuery("#popup-export-ot-modal").modal();
    });

    jQuery("#btn-export-al").click(function (event) {
        jQuery("#popup-export-al-modal").modal("hide");
        requestExportAL();
    });

    jQuery("#btn-export-ot").click(function (event) {
        jQuery("#popup-export-ot-modal").modal("hide");
        requestExportOT();
    });
    jQuery(".notify-list a").click(function (event) {
        $(this).hide()
        noti_id = $(this).attr('noti_id');
        removeNotification(noti_id);
    });
    jQuery('.img-yellow-card').click(function (event) {
        event.preventDefault();

        /* show date foul */
        const dateFoul = $(this).closest('span').find('.date-foul-yellow-card').val();
        $('#dateFoulYellowCard').val(dateFoul);

        /* show details */
        const details = $(this).closest('span').find('.details-yellow-card').val();
        $('#detailsYellowCard').html(details);

        /* show memo */
        const memo = $(this).closest('span').find('.memo-yellow-card').val();
        $('#memoYellowCard').html(memo);

        /*show images */
        const images = $(this).closest('span').find('.images-yellow-card').html();
        $('.gallery-yellow-card').html('');
        $('.gallery-yellow-card').append(images);
        jQuery("#modalYellowCardUser").modal();
    });


    $('.evidence-images').venobox({ closeBackground: 'rgba(68,68,68,0)', frameheight: '500' });
    // utilsModule().initStickyHeader();
});


$(document).ready(function () {
    var venoImagesProof = $('.images-proof').venobox({
        cb_post_close : function(){
            $("#modalYellowCardUser").modal('show');
        },
        cb_after_nav  : function(obj, gallIndex, thenext, theprev){
            const countImages = $('.gallery-yellow-card').find('#countImages').val()
            if(Number(gallIndex) > Number(countImages)){
                venoImagesProof.VBclose()
            }
        },
        frameheight: '500'
    });

    $('.gallery-yellow-card').on("click", function(){
        var id = $(this).find('.id-yellow').val();
        $('#id-first-image-' + id).click();
        $("#modalYellowCardUser").modal('hide');
    })

    $(".content").slice(0, 10).show();
    $("#loadMore").on("click", function (e) {
        e.preventDefault();
        $(".content:hidden").slice(0, 10).slideDown();
        if ($(".content:hidden").length == 0) {
            $("#loadMore").hide();
        }
    });


    // nhi: show datepicker not out of the box - 20200413
    jQuery(window).resize(function () {
        var window_width = jQuery(window).width();
        var left_datepicker = window_width / 2 - 150;
        if (jQuery("body").hasClass("modal-open")) {
            jQuery("#ui-monthpicker-div").css("left", $("input#input-al-date").offset().left);
        } else {
            jQuery("#ui-monthpicker-div").css("left", $("input.hasMonthpicker").offset().left);
            jQuery("#ui-datepicker-div").css("left", $("input.hasDatepicker").offset().left);
        }
    });
    // end

})

function requestExportAL() {
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'export/exportAl',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: {
            date: jQuery("#input-al-date").val(),
        }
    })
        .done(function (response) {
            utilsModule().hideLoadingModal();
            if (response.success) {
                var file = response.file;
                //location.href = baseUrl + file;
                window.open(baseUrl + file, '_blank');
                return;
            } else {
                swal(response.error, {
                    buttons: {
                        cancel: "OK",
                    },
                });
                return;
            }
        });
}


function requestExportOT() {
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'export/exportOt',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: {
            dateFrom: jQuery("#exportFromDate").val(),
            dateTo: jQuery("#exportToDate").val()
        }
    })
    .done(function (response) {
        console.log(response);
        utilsModule().hideLoadingModal();
        if (response.error) {
            swal(response.error, {
                buttons: {
                    cancel: "OK",
                },
            });
            return;

        } else {
            if (response.pdf) {
                window.open(baseUrl + response.pdf);
            }
            // if (response.file) {
            //     location.href = baseUrl + response.file;
            // }
        }
    })
    .fail(function (response) {
        console.log("error");
        console.log(response);
    });
}
function removeNotification(id) {

    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'notification/delete',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: {
            id: id
        }
    })
        .done(function (response) {
            data = JSON.parse(response);
            html = '';
            $.each(data.list_noti, function (index, value) {
                var date = new Date(value.DateSubmitted);

                var year = date.getFullYear();
                var month = date.getMonth() + 1;
                var day = date.getDate();
                var hours = date.getHours();
                var minutes = date.getMinutes();
                var seconds = date.getSeconds();
                time = year + "-" + month + "-" + day + " " + hours + ":" + minutes + ":" + seconds;
                html += '<li onclick="removeNotification(' + value.ID + ')"><a href="#" class="user-list-item notification-a" noti_id=' + value.ID + '  > <div class="user-desc"> <span class="name">' + value.Type + '</span> <span class="desc" >'
                html += value.Message + '</span> <span class="time">' + time + '</span> </div> </a> </li>'

            })
            html += '<li class="all-msgs text-center"><p class="m-0"><a href="' + baseUrl + 'notification/index' + '">See all Messages</a></p>  </li>';
            $('.notify-list').html(html)
            $('#count_noti').attr('data-count', data.count)
        });
}

//Get the button
var topBtn = document.getElementById("topBtn");
var downBtn = document.getElementById("downBtn");
// When the user scrolls down 20px from the top of the document, show the button
window.onscroll = function () { scrollFunction() };
scrollingElement = (document.scrollingElement || document.body)

function scrollFunction() {
    if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
        topBtn.style.display = "block";
        downBtn.style.display = "none";
    } else {
        downBtn.style.display = "block";
        topBtn.style.display = "none";
    }
}
// When the user clicks on the button, scroll to the top of the document
function topFunction() {
    $(scrollingElement).animate({
        scrollTop: 0
    }, 500);
    downBtn.style.display = "block";
    topBtn.style.display = "none";
}

function bottomFunction() {
    $(scrollingElement).animate({
        scrollTop: document.body.scrollHeight
    }, 500);
    topBtn.style.display = "block";
    downBtn.style.display = "none";
}


