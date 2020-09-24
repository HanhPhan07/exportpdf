jQuery(document).ready(function() {

    datepickerModule2.initial(["#dateFrom","#dateTo"]);

    jQuery("#multiple-select").select2();

    $('.fromtime').timepicker({
        timeFormat: 'HH:mm',
        dropdown: true,
        scrollbar: true,
        interval: 15,
        showMeridian: false
    });
    $('.totime').timepicker({
        timeFormat: 'HH:mm',
        interval: 15,
        dropdown: true,
        scrollbar: true,
        showMeridian: false
    });

    $("#dateTo, #dateFrom").on("change", function() {
        var dateFrom = $("#dateFrom").val();
        var dateTo = $("#dateTo").val();

        if(dateTo != ""){
            // Change 
            jQuery.ajax({
                type: 'post',
                url: '/admin/shift/getStaffIdsByDateQuitJob',
                headers: {'X-CSRF-TOKEN': csrfToken},
                dataType: "JSON",
                data: {
                    date_to: dateTo
                }
            })
            .done(function (response) {
                $('#multiple-select').empty().val(null).trigger('change');
                $.each(response, function(_,staffId){
                    var newOption = new Option(staffId,staffId, false, false);
                    $('#multiple-select').append(newOption);
                })
                $('#multiple-select').trigger('change');
            });
        }

        if (dateFrom == "" || dateTo == "") return;
        if (dateTo < dateFrom) {
            swal('Date to cannot set before date from.', {
                buttons: {
                    cancel: "OK",
                },
            });
            $("#dateTo").val("");
        }
    })

    jQuery("#btn-shift-click").click(function(event) {
        jQuery(".al-error").hide();
        var f = $('#dateFrom').val();
        var t = $('#dateTo').val();
        if (f == '' || t == '') {
            swal('Please input date.', {
                buttons: {
                    cancel: "OK",
                },
            });
        }
        else {
            requestMkShift();
        }
    });

    jQuery("#btn-clear-shift-click").click(function (event) {
        jQuery('#myForm').find('input').val('');
        jQuery('#myForm').find('select').val(1).change();
    });

    jQuery('#myForm select.form-control').change(function (event) {
        var ref = $(this).attr('data-refer');
        var shift = $(this).val();
        $("#fromtime-"+ref).val(shiftData[shift]['fromtime']);
        $("#totime-"+ref).val(shiftData[shift]['totime']);
    })

    $(".btn-insert-shift").on('click', function() { insertShiftForTeam(this);})
    // nhi: show datepicker not out of the box - 20200413
    jQuery(window).resize(function() {
        var window_width = jQuery(window).width();
        var left_datepicker = window_width/2 - 150;
        jQuery("#ui-monthpicker-div").css("left",left_datepicker);
        jQuery("#ui-datepicker-div").css("left",left_datepicker);
    });
    // end 
});

$(document).on("click",".btn-delete-shift",function(){
    var data = {
        "from": $("#dateFrom").val(),
        "to": $("#dateTo").val(),
        'staffIds': $("#multiple-select").val(),
    }
    if (data.from == "" || data.to == "") {
        swal('Please input date.', {
            buttons: {
                cancel: "OK",
            },
        });
        return;
    }
    var urlPost = baseUrl + 'admin/shift/deleteShiftCustom';
    swal({
      title: "Are you sure?",
      text: "Once deleted, you will not be able to recover!",
      icon: "warning",
      buttons: true,
      dangerMode: true,
    })
    .then((willDelete) => {
      if (willDelete) {
        utilsModule().displayLoadingModal();
        jQuery.ajax({
            type: 'post',
            url: urlPost,
            headers: {'X-CSRF-TOKEN': csrfToken},
            data: data
        })
        .done(function (response) {
            if (response.success == 1) {
                utilsModule().hideLoadingModal();
                swal("Deleted!");
                return;
            } else {
                utilsModule().hideLoadingModal();
                swal(response.error, {
                    buttons: {
                        cancel: "OK",
                    },
                });
                return;
            }
        });
      }
    });

    

})

function insertShiftForTeam(_this) {
    var teamID = $(_this).data('team');
    var employeeID = $(_this).data('employee-id');
    var urlPost = baseUrl + 'admin/shift/mkshift';
    var data = {
        "from": $("#dateFrom").val(),
        "to": $("#dateTo").val(),
        ["shift["+teamID+"]"]: $("select[name='shift["+teamID+"]']").val(),
        "is_insert_individual": true,
        ["fromtime["+teamID+"]"]: $("#fromtime-" + teamID).val(),
        ["totime["+teamID+"]"]: $("#totime-" + teamID).val(),
        'staffIds': []
    }
    if (data.from == "" || data.to == "") {
        swal('Please input date.', {
            buttons: {
                cancel: "OK",
            },
        });
        return;
    }

    if(teamID == "multiple"){
        data.staffIds = $("#multiple-select").val();
        urlPost = baseUrl + 'admin/shift/mkshiftCustom';
    }

    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: urlPost,
        headers: {'X-CSRF-TOKEN': csrfToken},
        data: data
    })
    .done(function (response) {
        if (response.success == 1) {
            utilsModule().hideLoadingModal();
            swal("Successful!");
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

function requestMkShift()
{
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'admin/shift/mkshift',
        headers: {'X-CSRF-TOKEN': csrfToken},
        data: jQuery('#myForm').serializeArray()
    })
    .done(function (response) {
        if (response.success) {
            utilsModule().hideLoadingModal();
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
var _list_from_time={
    0: '08:00',
    1:'07:00',
    2: '10:00',
    3:'12:00',
    4: '07:30',
};
var _list_to_time={
    0: '17:00',
    1:'16:00',
    2: '19:00',
    3:'21:00',
    4: '16:30',
};

/*--DANDEV--*/
jQuery(document).ready(function() {
    window.onload = date_time('date_time');
    jQuery('.js-data-staff-ajax').select2({
        minimumInputLength: 3,
        ajax: {
            url: baseUrl+'admin/shift/get_staff_by_id',
            type: 'POST',
            dataType: 'json',
            data: function (params) {
                return {
                    staff_id: params.term
                };
            },
            processResults: function (data, params) {
                return {
                    results: jQuery.map(data, function (item) {
                        return {
                            text: item.StaffID +' - '+ item.StaffName ,
                            id: item.StaffID,
                            data: item
                        };
                    })
                };
            }
        }
    });
    datepickerModule2.initial(["#staff_date_from","#staff_date_to"]);
    $("#staff_date_from, #staff_date_to").on("change", function() {
        var dateFrom = $("#staff_date_from").val();
        var dateTo = $("#staff_date_to").val();
        if (dateFrom == "" || dateTo == "") return;
        if (dateTo < dateFrom) {
            swal('Date to cannot set before date from.', {
                buttons: {
                    cancel: "OK",
                },
            });
            $("#staff_date_to").val("");
        }
    })
    jQuery('.js-data-staff-ajax').change(function(){
        utilsModule().displayLoadingModal();
        jQuery.ajax({
            url: baseUrl+'admin/shift/get_staff_by_id_full',
            type: 'POST',
            dataType: 'json',
            data: {staff_id:jQuery(this).val()},
            success:function(res){
                if(res!=undefined){
                    jQuery('#staff_full_name').text(res.StaffName);
                    if (!res.TBLMStaff3) {
                        swal('There is no Staff3 information of this staff.', {
                            buttons: {
                                cancel: "OK",
                            },
                        });
                    } else {
                        jQuery('#staff_from_team').val(res.TBLMStaff3.Team).change();
                    }
                }
                utilsModule().hideLoadingModal();
            }
        });

    });
    jQuery("#btn-clear-change-team").click(function (event) {
        jQuery('.change-team').find('input').val('');
        jQuery('.change-team').find('select').val('').change();
    });
    jQuery("#btn-change-team").click(function(event) {
        var f = jQuery('#staff_date_from').val();
        var t = jQuery('#staff_date_to').val();

        if(jQuery('.js-data-staff-ajax').val()==undefined || jQuery('.js-data-staff-ajax').val()==''){
            swal('Please choose a staff.', {
                buttons: {
                    cancel: "OK",
                },
            });
        }else{
            if(jQuery('#staff_from_team').val()==jQuery('#staff_to_team').val()){
                swal('Please choose a destination team other.', {
                    buttons: {
                        cancel: "OK",
                    },
                });
            }else{
                if (f == '' || t == '') {
                    swal('Please input date.', {
                        buttons: {
                            cancel: "OK",
                        },
                    });
                }
                else {
                    requestChangeTeam();

                }
            }
        }
    });

    var timePickers = [
        {
            selector: "#fromTime",
            options: {
                minuteStep: 15,
                showMeridian: false
            }
        },
        {
            selector: "#toTime",
            options: {
                minuteStep: 15,
                showMeridian: false
            }
        }
    ];
    timepickerModule.initial(timePickers);
    var _renderTimePicker = function() {
        $('#fromTime').replaceWith('<input class="form-control " type="text" id="fromTime" name="FromTime" placeholder="from time" required>');
        $('#toTime').replaceWith('<input class="form-control " type="text" id="toTime" name="ToTime" placeholder="to time" required>');

        timepickerModule.initial(timePickers);

        var currentShift = $("#staff_to_shift").val();



        if (!currentShift) return;

        timepickerModule.setTime(timePickers[0].selector, _list_from_time[currentShift]);
        timepickerModule.setTime(timePickers[1].selector, _list_to_time[currentShift]);
    }
    _renderTimePicker();
    $('#staff_to_shift').change(function(){
        _renderTimePicker();
    });
});


function requestChangeTeam(){
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'POST',
        url: baseUrl + 'admin/shift/get_change_team',
        headers: {'X-CSRF-TOKEN': csrfToken},
        data: jQuery('#frm_change_team').serializeArray()
    })
        .done(function (response) {
            if (response.success) {
                swal('Change Team Successful !', {
                    buttons: {
                        cancel: "OK",
                    }
                });
                jQuery('#staff_from_team').val(jQuery('#staff_to_team').val());
                return;
            } else {
                swal(response.message, {
                    buttons: {
                        cancel: "OK",
                    },
                });
                return;
            }
            utilsModule().hideLoadingModal();
        });
}


