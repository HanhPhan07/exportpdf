const date_type_array = ["DateUpdated", "DateFoul"];

$(document).ready(function () {
    $('.evidence-images').venobox({ closeBackground: 'rgba(68,68,68,0)', frameheight: '600' });
    $('.show-change-status-modal').on('click', function (e) {
        e.preventDefault();
        const id = $(this).closest('table').find('.id-yellow-card').data('id');
        const status = $(this).closest('table').find('.td-status').data('status');
        $('#idYellowCardClick').val(id);
        $('.div-change-status .status').each(function (index) {
            if ($(this).val() == status) {
                $(this).prop("checked", true);
            }
        })
        $("#change-status-modal").modal();
    })
    $('.gallery').on('click', function () {
        // e.preventDefault();
        $('#gallery-photo-add').prop("hidden", false);
    })
    $('.btn-edit').on('click', function () {
        const id = $(this).closest('table').find('.id-yellow-card').data('id');
        $('#modalYellowCard .modal-content').append('<input type="hidden" value="' + id + '" id="idYellowCardEdit"/>')
        /* change info */
        $('#modalYellowCard .modal-title').html("Edit Yellow Card");

        /* get values */
        const staffId = $(this).closest('table').find('.td-staff-id').data('value');
        const staffName = $(this).closest('table').find('.td-staff-name').data('value');
        const dateFoul = $(this).closest('table').find('.td-date-foul').data('value');
        const details = $(this).closest('table').find('.td-details').data('value');
        const memo = $(this).closest('table').find('.td-memo').data('value');
        const images = $(this).closest('table').find('.td-evidence-images').html();

        /*show images */
        $('#gallery-photo-add').prop("hidden", true);
        $('.gallery').html('');
        $('.gallery').append(images);
        $('.gallery').append('<br><a type="button" style="margin-top:10px; color:white" class="btn btn-secondary">Change</a><input id="notChange" hidden />');
        $('.gallery').attr("style", "cursor:pointer");

        /* show staff list */
        showStaffIDList(staffId);

        /* show name */
        /* show date foul */
        $('#dateFoul').val(dateFoul);

         /* show details */
        $('#textareaDetails').html(details);

        /* show memo */
        $('#textMemo').html(memo);

        /* show MODAL */
        $('#modalYellowCard').modal();
        // editYellowCard(id);
    })

    $('.btn-delete').on('click', function () {
        const id = $(this).closest('table').find('.id-yellow-card').data('id');
        deleteYellowCard(id);
        // editYellowCard(id);
    })

    /* upload images yellow card*/
    $('#gallery-photo-add').on('change', function () {
        imagesPreview(this, 'div.gallery');
        setTimeout(function () {
            if ($('.files-selected-not-allow').length) {
                $("#btnClearFileType").click();
                alert('Please select only image file.');
            }
            utilsModule().hideLoadingModal();
        }, 1500);
    });
    /* clear file type images */
    $("#btnClearFileType").on('click', function (e) {
        e.preventDefault();
        $('#gallery-photo-add').val('');
    });

    $('.after-submit').on('click', function (e) {
        e.preventDefault();
        const id = $(this).closest('table').find('.id-yellow-card').data('id');
        const status = $(this).closest('table').find('.td-status').data('status');
        $('#idYellowCardClick').val(id);
        $('.div-change-status .status').each(function (index) {
            if ($(this).val() == status) {
                $(this).prop("checked", true);
            }
        })
        $("#change-status-modal").modal();
    })
    $('#btn-submit-status').on('click', function (e) {
        swal({
            title: "Please wait!",
            buttons: false
        });
        e.preventDefault();
        $.ajax({
            url: baseUrl + 'admin/yellowCard/changeStatus',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            type: 'post',
            data: {
                'status': $('.status:checked').val(),
                'id': $('#idYellowCardClick').val()
            },
            success: function (response) {
                if (response.success) {
                    $('.table-yellow-card .td-status').each(function (index) {
                        if ($(this).data('table-id') == $('#idYellowCardClick').val()) {
                            var text = '';
                            if ($('.status:checked').val() == "Pending") {
                                text = '<span class="badge badge-warning after-submit" style="font-size:14px; padding:5px 10px">' + $('.status:checked').val() + '</span><br><a class="after-submit" style="font-size:12px">Reload page if to change</a>';
                            } else {
                                text = '<span class="badge badge-success after-submit" style="font-size:14px; padding:5px 10px">' + $('.status:checked').val() + '</span><br><a class="after-submit" style="font-size:12px">Reload page if to change</a>';
                            }
                            $(this).html('');
                            $(this).html(text);
                            $(this).attr("data-status", $('.status:checked').val());
                        }
                    });
                    $("#change-status-modal").modal('hide');
                    swal({
                        icon: "success",
                        title: "Changed!",
                        buttons: {
                            cancel: "OK",
                        },
                    });
                } else {
                    swal({
                        icon: "error",
                        title: "Have error.",
                        buttons: {
                            cancel: "OK",
                        },
                    });
                }
            }
        })
    })
    $('.btn-filter').on('click', function (e) {
        e.preventDefault();
        $('#filter-table-modal').modal();
    })
    $('.btn-sort').on('click', function (e) {
        e.preventDefault();
        const properties = $(this).closest('div').find('.properties').val();
        const order = $(this).closest('div').find('.order').val();
        location.href = baseUrl + 'admin/yellow-card/col-' + properties + '/sort-' + order;
    })
    $('.btn-search').on('click', function (e) {
        e.preventDefault();
        const key = $(this).closest("div").find('.input-search').val();
        if (key.indexOf('/') > -1) {
            alert('Please enter only letters and numbers. If you want to search by date please use filter.');
        } else {
            location.href = baseUrl + 'admin/yellow-card/search-' + key;
        }
    })
    // FILTER
    $('#selectFilter').change(function () {
        if($(this).val() == 'Status'){
            $('#keyFilterStatus').show();
            $('#keyFilterID').hide();
            $('#keyFilterDate').hide();
        } else if($(this).val() == 'StaffID'){
            showStaffIDYellowCard(0)
            $('#keyFilterStatus').hide();
            $('#keyFilterID').show();
            $('#keyFilterDate').hide();
        } else{
            $('#keyFilterStatus').hide();
            $('#keyFilterID').hide();
            $('#keyFilterDate').show();
        }

        if (date_type_array.indexOf($(this).val()) > -1) {
            $('#keyFilterDate').val('')
            $('#keyFilterDate').datepicker({
                defaultDate: "+1w",
                changeMonth: true,
                numberOfMonths: 1,
                dateFormat: "yy/mm/dd",
                // minDate: 0,
            });
        } else {
            $("#keyFilterDate").datepicker("destroy")
            $('#keyFilterDate').val('')
        }

    })

    $('#btn-submit-filter').on('click', function (e) {
        e.preventDefault();
        const option = $('#selectFilter').val();
        if(option == 'Status'){
            var key = $('#keyFilterStatus').val();
        } else if(option == 'StaffID'){
            var key = $('#keyFilterID').val();
        }else {
            var key = $('#keyFilterDate').val();
            if (date_type_array.indexOf(option) > -1) {
                key = moment(key).format('YYYY-MM-DD');
            } else {
                if (key.indexOf('/') > -1) {
                    alert('Please enter only letters and numbers.');
                    return;
                }
            }
        }

        location.href = baseUrl + 'admin/yellow-card/filter/option-' + option + '/key-' + key;
    })
    // END FILTER
});
/* TODO: END document */
$("#dateFoul")
    .datepicker({
        defaultDate: "+1w",
        // changeMonth: true,
        numberOfMonths: 1,
        dateFormat: "yy/mm/dd",
        // disable past date
        maxDate: +1
    })
    .on("click", function () {
        optimizePositionDatePicker('dateFoul');
    });
$(window).resize(function () {
    $("#ui-datepicker-div").css("display", "none");
});

function getPosition(element) {
    var rect = element.getBoundingClientRect();
    return { x: rect.left, y: rect.top };
}
function optimizePositionDatePicker(str_id_datepicker) {
    var element = document.getElementById(str_id_datepicker);
    var pos = getPosition(element);
    $("#ui-datepicker-div").css("left", pos.x);
    $("#ui-datepicker-div").css("top", pos.y + 40);
    $("#ui-datepicker-div").css("display", "block");
}

function showStaffIDYellowCard(staffid){
    $.ajax({
        'url': baseUrl + 'admin/yellowCard/getStaffID',
        'type': 'get',
        'dataType': 'json',
    }).done(function(response){
        $('#keyFilterID').html('')
        $.each(response, function(index, value){
            var option = ''
            if (staffid == value.StaffID) {
                option += '<option value="' + value.StaffID + '" selected="selected">' + value.StaffID + '</option>';
            } else {
                option += '<option value="' + value.StaffID + '">' + value.StaffID  + '</option>';
            }
            $('#keyFilterID').append(option)
        })
    })
}

// Multiple images preview in browser
var imagesPreview = function (input, placeToInsertImagePreview) {
    utilsModule().displayLoadingModal();
    if (input.files) {
        var filesAmount = input.files.length;
        $(placeToInsertImagePreview).html('');
        for (i = 0; i < filesAmount; i++) {
            /* check ext file */
            var reader = new FileReader();
            var listExtImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff', 'bmp'];
            var file = input.files[i].name;
            var dotSeperate = file.lastIndexOf(".") + 1;
            var extFile = file.substr(dotSeperate, file.length).toLowerCase();
            /* reader images */
            reader.onload = function (event) {
                if ($.inArray(extFile, listExtImage) !== -1) {
                    $($.parseHTML('<img>')).attr('src', event.target.result).attr('style', 'width:50px; height:50px').appendTo(placeToInsertImagePreview);
                } else {
                    $($.parseHTML('<input>')).attr('class', 'files-selected-not-allow').attr('hidden', 'hidden').appendTo(placeToInsertImagePreview);
                }
            }
            reader.readAsDataURL(input.files[i]);
        }
    }
};

function deleteYellowCard(id) {
    // var form = event.target.form; // storing the form
    swal({
        title: "Are you sure you want to delete this record?",
        icon: "error",
        buttons: true,
        dangerMode: true,
    })
        .then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    type: 'post',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    url: baseUrl + 'admin/yellowCard/delete',
                    data: { 'id': id }
                })
                    .done(function (response) {
                        if (response.success) {
                            swal("This record has been deleted!", {
                                icon: "success",
                            })
                                .then((reload) => {
                                    if (reload) {
                                        window.location.reload(true);
                                    }
                                });
                        }
                    });
            }
        });
}


function showStaffIDList(staffID) {
    $("#select-staff-id").html('');
    $.ajax({
        url: baseUrl + 'admin/yellowCard/listStaffID',
        type: 'get',
    }).done(function (response) {
        var list = '';
        $.each(response, function (index, value) {
            if (staffID == value.StaffID) {
                list += '<option value="' + value.StaffID + '" selected="selected">' + value.StaffID + ' - ' + value.StaffName + '</option>';
            } else {
                list += '<option value="' + value.StaffID + '">' + value.StaffID + ' - ' + value.StaffName + '</option>';
            }
        });
        $("#select-staff-id").html(list);
    });
}

function editYellowCard(id) {
    $.ajax({
        url: baseUrl + 'admin/yellowCard/edit',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        type: 'post',
        data: { 'id': id },
    }).done(function (response) {

    });
}

function beforeRender() {
    var url_string = window.location.href;
    if ($('.properties').length && $('.order').length) {
        /* sort */
        if (url_string.indexOf('/sort-') > -1) { /* check url contain sort */
            var col = url_string.split("/col-")[1];
            var sort = url_string.split("/sort-")[1];
            col = col.substr(0, col.indexOf('/'));
            $('.properties').val(col);
            $('.order').val(sort);

            /* search */
        } else if (url_string.indexOf('/search-') > -1) {
            $('.properties').val('DateUpdated');
            $('.order').val('DESC');
            var key = url_string.split('/search-')[1];
            $('.input-search').val(key);
        } else {
            $('.properties').val('DateUpdated');
            $('.order').val('DESC');
        }
    }
    if(url_string.indexOf('/filter/') > -1){
        var option = url_string.split("/option-")[1];
        var key = url_string.split("/key-")[1];
        option = option.substr(0, option.indexOf('/'));
        $('#selectFilter').val(option)
        if(option == 'Status'){
            $('#keyFilterStatus').val(key)
            $('#keyFilterStatus').show()
            $('#keyFilterID').hide();
            $('#keyFilterDate').hide();
        } else if(option == 'StaffID'){
            showStaffIDYellowCard(key)
            $('#keyFilterStatus').hide()
            $('#keyFilterID').show();
            $('#keyFilterDate').hide();
        } else {
            $('#keyFilterDate').datepicker({
                defaultDate: "+1w",
                changeMonth: true,
                numberOfMonths: 1,
                dateFormat: "yy/mm/dd",
                // minDate: 0,
            });
            key = moment(key).format('YYYY/MM/DD')
            $('#keyFilterDate').val(key)
            $('#keyFilterStatus').hide()
            $('#keyFilterID').hide();
            $('#keyFilterDate').show();
        }

    }
}
window.onload = beforeRender;
