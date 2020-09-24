jQuery(document).ready(function () {

    monthPickerModule.initial(['#input-al-date']);
    dateRangePickerModule.dateRange("#periodFrom", "#periodTo");

    jQuery("#staff-level").click(function (event) {
        requestExport();
    });

    jQuery("#staff-emerg").click(function (event) {
        requestExportEmerg();
    });

    jQuery("#btn-current-period").click(function (event) {
        jQuery("#evaluation-period-modal").modal('hide');
        jQuery("#evaluation-period-current-modal").modal();
    });
    jQuery("#btnCloseCurrentEvaluationPeriod").click(function (event) {
        event.preventDefault();
        jQuery("#evaluation-period-modal").modal('show');
        jQuery("#evaluation-period-current-modal").modal('hide');
    });

    jQuery("#application-leave").click(function (event) {
        jQuery("#application-leave-modal").modal();
    });

    jQuery("#insert-shift").click(function (event) {
        url = __baseUrl + 'admin/shift'
        jQuery(location).attr('href', url);
    });
    jQuery("#change-team").click(function (event) {
        url = __baseUrl + 'admin/shift/change_team'
        jQuery(location).attr('href', url);
    });
    jQuery("#btn-holiday").click(function (event) {
        url = __baseUrl + 'admin/holiday'
        jQuery(location).attr('href', url);
    });
    jQuery("#btn-al-submit").click(function (event) {
        jQuery(".al-error").hide();
        if (!jQuery("#input-al-staff-id").val()) {
            jQuery("#al-staff-id-error").show();
            return false;
        }

        jQuery("#application-leave-modal").modal("hide");
        requestExportAL();
    });

    /* start events of evaluation */
    /* click "Evaluation System" */
    jQuery("#evaluation-period").click(function (event) {
        getCurrentPeriod();
        jQuery("#evaluation-period-modal").modal();
        rangeDate();
    });
    /* submit Evaluation Period */
    jQuery("#btn-evaluation-period").click(function (event) {
        if (jQuery("#startPeriod").val() == "" || jQuery("#endPeriod").val() == "") {
            alert("Please select evaluation period.");
        } else if (jQuery("#evaluation_id").val().length < 1 || jQuery("#evaluation_id").val().length > 10) {
            alert("Please enter ID field and only up to 10 characters.");
        } else if (jQuery("#evaluation_title").val().length > 50) {
            alert("Please enter only up to 50 characters in Title field.");
        }
        else {
            setEvaluationPeriod();
        }
    });
    /* click Pause/continue Time Left */
    jQuery("#btnActionPeriod").on('click', function (event) {
        event.preventDefault();
        if (jQuery("#btnActionPeriod").html() == "Pause") {
            setActionPeriod(0);
        } else {
            setActionPeriod(1);
        }
    });
    /* delete period */
    jQuery(document).on('click', '.del-period', function (e) {
        e.preventDefault();
        var id = jQuery(this).closest('p').find('.id-period').html();
        deletePeriod(id);
    })
    /* end events of evaluation */

    /* TODO: start yellow card */
    /* show modal */
    jQuery('#show-yellow-card-modal').on('click', function () {
        showStaffIDList();
        jQuery('#modalYellowCard').modal();
    });
    /* upload images yellow card*/
    jQuery('#gallery-photo-add').on('change', function () {
        imagesPreview(this, 'div.gallery');
        setTimeout(function () {
            if (jQuery('.files-selected-not-allow').length) {
                jQuery("#btnClearFileType").click();
                alert('Please select only image file.');
            }
            utilsModule().hideLoadingModal();
        }, 1500);
    });
    /* clear file type images */
    jQuery("#btnClearFileType").on('click', function (e) {
        e.preventDefault();
        jQuery('#gallery-photo-add').val('');
    });

    jQuery("#login-as-staff").click(function () {
        showStaffListToSelect();
        jQuery('#login-as-staff-modal').modal();
    });
    monthPickerModule.initial(['#input-ot-date']);

    jQuery("#btn-overtime-info-export").click(function (event) {
        event.preventDefault();
        jQuery("#popup-export-ot-all-modal").modal();
    });

    monthPickerModule.initial(['#input-ot-summary-date']);
    jQuery("#btn-overtime-summary").click(function (event) {
        event.preventDefault();
        jQuery("#popup-export-ot-summary-all-modal").modal();
    });
    jQuery("#btn-export-ot-all").click(function (event) {
        jQuery("#popup-export-ot-all-modal").modal("hide");
        requestExportOTAll();
    });
    jQuery("#btn-export-ot-summary-all").click(function (event) {
        jQuery("#popup-export-ot-summary-all-modal").modal("hide");
        requestExportOTSummaryAll()
    });
    jQuery("#import-salary").click(function (event) {
        event.preventDefault();
        jQuery("#import-salary-modal").modal();
    });
    monthPickerModule.initial(['#salary-month']);
    monthPickerModule.initial(['#all-salary-month']);
    jQuery("#import-salary-detail").click(function (event) {
        event.preventDefault();
        if (jQuery("#check-is-modal").attr('progress-modal') == "true") {
            jQuery("#all-salary-details-modal").modal();
        } else {
            jQuery("#import-salary-detail-modal").modal();
        }
        showStaffListToSelect();
        showFilesZip();
    });
    // nhi: show datepicker not out of the box - 20200413
    jQuery(window).resize(function () {
        var window_width = jQuery(window).width();
        var left_datepicker = window_width / 2 - 150;
        jQuery("#ui-monthpicker-div").css("left", left_datepicker);
        jQuery("#ui-datepicker-div").css("left", left_datepicker);
    });
    // end
    jQuery("#btn-export-salary-detail-month").click(function (event) {
        var month = jQuery('#salary-month').val();
        if (month == "") {
            alert("Please select month to export salary!");
        } else {
            requestExportSalaryDetails();
        }
    });
    // jQuery("#btn-all-salary-details-to-zip").click(function(event){
    //     requestAllSalaryDetailsToZip();
    // });
    jQuery("#btn-import-salary-submit").click(function (event) {
        var fileVal = jQuery('#input-excel-file').val();
        if (fileVal == "") {
            alert("Please select your salary file!");
        } else {
            importSalary();
        }
    });
    jQuery("#confirm-list").click(function (event) {
        requestExportConfirmationList();
    });
});
/* end jQuery(document) */


function showStaffIDList() {
    jQuery("#select-staff-id").html('');
    jQuery.ajax({
        url: baseUrl + 'admin/yellowCard/listStaffID',
        type: 'get',
    }).done(function (response) {
        var list = '';
        jQuery.each(response, function (index, value) {
            list += '<option value="' + value.StaffID + '">' + value.StaffID + ' - ' + value.StaffName + '</option>';
        });
        jQuery("#select-staff-id").html(list);
    });
}

var myVar = '';
/* submit evaluation period */
function setEvaluationPeriod() {
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        url: baseUrl + 'admin/mypage/setEvaluationPeriod',
        type: 'post',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: {
            'id': jQuery('#evaluation_id').val(),
            'title': jQuery('#evaluation_title').val(),
            'start': jQuery("#startPeriod").val(),
            'end': jQuery("#endPeriod").val(),
        },
    })
        .done(function (response) {
            if (response.status) {
                if (response.status == "success") {
                    swal({
                        icon: "success",
                        title: "Successfully!",
                        buttons: {
                            cancel: "OK",
                        },
                    });
                } else if (response.status == "duplicate") {
                    alert("ID already exists. Please enter another ID.");
                    utilsModule().hideLoadingModal();
                }
                else {
                    swal({
                        icon: "error",
                        title: "Error!",
                        buttons: {
                            cancel: "OK",
                        },
                    });
                }
            }
            if (response.status == "success") {
                getCurrentPeriod();
                var now = new Date().getTime();
                if (now >= Date.parse(jQuery("#startPeriod").val())) {
                    timeLeft(jQuery("#endPeriod").val());
                } else {
                    clearInterval(myVar);
                    jQuery("#btnActionPeriod").hide();
                }
            }
            jQuery("#startPeriod").val('');
            jQuery("#endPeriod").val('');
            jQuery("#startPeriod").datepicker("option", "maxDate", null);
            jQuery("#endPeriod").datepicker("option", "minDate", 0);
        });
}

/* setting current period */
function getCurrentPeriod() {
    jQuery.ajax({
        type: 'get',
        url: baseUrl + 'admin/mypage/currentPeriod',
    })
        .done(function (response) {
            if (response.data) {
                jQuery("#id_current_period").html(response.ID);
                jQuery("#title_current_period").html(response.title);
                /* current period */
                jQuery("#currentFrom").html(response.currentFrom);
                jQuery("#currentTo").html(response.currentTo);
                if (response.ended) {
                    jQuery(".div-current").hide();
                } else {
                    if (response.started) {
                        if (response.running == 1) {
                            timeLeft(response.toTime);
                            jQuery("#btnActionPeriod").text("Pause");
                        } else {
                            jQuery("#btnActionPeriod").text("Continue");
                            clearInterval(myVar);
                            jQuery("#pausing").html("Pausing");
                            jQuery("#pausing").show();
                            jQuery("#btnActionPeriod").removeClass("btn btn-warning");
                            jQuery("#btnActionPeriod").addClass("btn btn-info");
                        }
                        jQuery("#btnActionPeriod").show();
                    } else {
                        jQuery("#timeLeft").html(response.timeLeft);
                    }
                    jQuery(".div-current").show();
                }
                jQuery("#btn-current-period").show();
            } else {
                jQuery("#btn-current-period").hide();
            }
        });
}

/* delete period */
function deletePeriod(id) {
    // var form = event.target.form; // storing the form
    swal({
        title: "Are you sure you want to delete this period?",
        icon: "error",
        buttons: true,
        dangerMode: true,
    })
        .then((willDelete) => {
            if (willDelete) {
                jQuery.ajax({
                    type: 'post',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    url: baseUrl + 'admin/mypage/deletePeriod',
                    data: { 'id': id }
                })
                    .done(function (response) {
                        if (response.success) {
                            swal("This period has been deleted!", {
                                icon: "success",
                            });
                        }
                    });
            }
        });
}

/* pause/continue period */
function setActionPeriod(running) {
    if (running == 0) {
        jQuery("#btnActionPeriod").html("Continue");
    } else {
        jQuery("#btnActionPeriod").html("Pause");
    }

    jQuery.ajax({
        type: 'post',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        url: baseUrl + 'admin/mypage/actionPeriod',
        data: { 'running': running }
    })
        .done(function (response) {
            if (response.data) {
                if (response.value.Running == 0) {
                    jQuery("#timeLeft").hide();
                    jQuery("#pausing").html("Pausing");
                    jQuery("#pausing").show();
                    jQuery("#btnActionPeriod").removeClass("btn btn-warning");
                    jQuery("#btnActionPeriod").addClass("btn btn-info");
                } else {
                    timeLeft(response.value.EndDate);
                    jQuery("#pausing").hide();
                    jQuery("#timeLeft").show();
                    jQuery("#btnActionPeriod").removeClass("btn btn-info");
                    jQuery("#btnActionPeriod").addClass("btn btn-warning");
                }
            }
        });
}

// time left running
var timeLeft = function (toTime) {
    clearInterval(myVar);
    var countDownDate = Date.parse(toTime);
    myVar = setInterval(function () {
        var now = new Date().getTime();
        distance = countDownDate - now;
        if (distance > 0) {
            var clock = '';
            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            // Display the result in the element with id="demo"
            if (days >= 10) { clock = days + "d "; } else { clock = "0" + days + "d "; }
            if (hours >= 10) { clock += hours + "h "; } else { clock += "0" + hours + "h "; }
            if (minutes >= 10) { clock += minutes + "m "; } else { clock += "0" + minutes + "m "; }
            if (seconds >= 10) { clock += seconds + "s "; } else { clock += '0' + seconds + "s "; }
            document.getElementById("timeLeft").innerHTML = clock
        } else {
            jQuery(".div-current").hide();
        }
    }, 1000)
}

jQuery("#dateFoul")
    .datepicker({
        // defaultDate: "+1w",
        changeMonth: false,
        numberOfMonths: 1,
        dateFormat: "yy/mm/dd",
        // disable past date
        maxDate: 0
    })
    .on("click", function () {
        optimizePositionDatePicker('dateFoul');
    });

/* range datepicker */
function rangeDate() {
    var dateFormat = "yy/mm/dd",
        from = jQuery("#startPeriod")
            .datepicker({
                defaultDate: "+1w",
                changeMonth: true,
                numberOfMonths: 1,
                dateFormat: "yy/mm/dd",
                minDate: 0,
            })
            .on("change", function () {
                to.datepicker("option", "minDate", getDate(this));
            })
            .on("click", function () {
                optimizePositionDatePicker('startPeriod');
            }),
        to = jQuery("#endPeriod")
            .datepicker({
                defaultDate: "+1w",
                changeMonth: true,
                numberOfMonths: 1,
                dateFormat: "yy/mm/dd",
                minDate: 0,
            })
            .on("change", function () {
                from.datepicker("option", "maxDate", getDate(this));
            })
            .on("click", function () {
                optimizePositionDatePicker('endPeriod');
            });

    function getDate(element) {
        var date;
        try {
            date = jQuery.datepicker.parseDate(dateFormat, element.value);
        } catch (error) {
            date = null;
        }

        return date;
    }
}

/* fix position datepicker when resize */
jQuery(window).resize(function () {
    jQuery("#ui-datepicker-div").css("display", "none");
});
function getPosition(element) {
    var rect = element.getBoundingClientRect();
    return { x: rect.left, y: rect.top };
}
function optimizePositionDatePicker(str_id_datepicker) {
    var element = document.getElementById(str_id_datepicker);
    var pos = getPosition(element);
    jQuery("#ui-datepicker-div").css("left", pos.x);
    jQuery("#ui-datepicker-div").css("top", pos.y + 40);
    jQuery("#ui-datepicker-div").css("display", "block");
}

// Multiple images preview in browser
var imagesPreview = function (input, placeToInsertImagePreview) {
    utilsModule().displayLoadingModal();
    if (input.files) {
        var filesAmount = input.files.length;
        jQuery(placeToInsertImagePreview).html('');
        for (i = 0; i < filesAmount; i++) {
            /* check ext file */
            var reader = new FileReader();
            var listExtImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff', 'bmp'];
            var file = input.files[i].name;
            var dotSeperate = file.lastIndexOf(".") + 1;
            var extFile = file.substr(dotSeperate, file.length).toLowerCase();
            /* reader images */
            reader.onload = function (event) {
                if (jQuery.inArray(extFile, listExtImage) !== -1) {
                    jQuery(jQuery.parseHTML('<img>')).attr('src', event.target.result).attr('style', 'width:50px; height:50px').appendTo(placeToInsertImagePreview);
                } else {
                    jQuery(jQuery.parseHTML('<input>')).attr('class', 'files-selected-not-allow').attr('hidden', 'hidden').appendTo(placeToInsertImagePreview);
                }
            }
            reader.readAsDataURL(input.files[i]);
        }
    }
};

function requestExportAL() {
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'admin/mypage/exportAL',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: {
            staff_id: jQuery("#input-al-staff-id").val(),
            date: jQuery("#input-al-date").val(),
        }
    })
        .done(function (response) {
            utilsModule().hideLoadingModal();
            if (response.success) {
                var file = response.file;
                location.href = baseUrl + file;
                //window.open(baseUrl + file, '_blank');
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

function requestExportConfirmationList() {

    utilsModule().displayLoadingModal();
    window.location.href = baseUrl + 'admin/mypage/exportConfirmationList';
    setTimeout(function () {
        utilsModule().hideLoadingModal();
    }, 3000);
}

function requestExport() {
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'admin/mypage/exportSalaryLevel',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: {}
    })
        .done(function (response) {
            utilsModule().hideLoadingModal();
            if (response.success) {
                var file = response.file;
                location.href = baseUrl + file;
                //window.open(baseUrl + file, '_blank');
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
/* @author: Nhi
   @function: export salary detail by month
*/
function requestExportSalaryDetails() {
    var pdf_val = "";
    var excel_val = "";
    if (jQuery('input[name=pdf-detail]').is(':checked')) {
        pdf_val = jQuery('input[name=pdf-detail]').val();
    }
    if (jQuery('input[name=excel-detail]').is(':checked')) {
        excel_val = jQuery('input[name=excel-detail]').val();
    }
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'admin/mypage/exportSalaryDetailByMonth',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: {
            staff_id: jQuery("#select-staff-salary-id").val(),
            date: jQuery("#salary-month").val(),
            export_mode: jQuery('input[name=export-mode]:checked').val()
        }
    })
        .done(function (response) {
            console.log(response);
            utilsModule().hideLoadingModal();
            if (response.success) {
                if (response.pdf) {
                    window.open(baseUrl + response.pdf);
                }
                if (response.file) {
                    location.href = baseUrl + response.file;
                }
            } else {
                swal(response.error, {
                    buttons: {
                        cancel: "OK",
                    },
                });
                return;
            }
        })
        .fail(function (response) {
            console.log("error");
            console.log(response);
        });
}
/* end */

function requestExportEmerg() {
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'admin/mypage/emerg',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: {}
    })
        .done(function (response) {
            utilsModule().hideLoadingModal();
            if (response.success) {
                var file = response.file;
                location.href = baseUrl + file;
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

function showStaffListToSelect() {
    utilsModule().displayLoadingModal();
    jQuery("#select-login-staff-id").html('');
    jQuery("#select-staff-salary-id").html('')
    jQuery("#staffId").html('')
    jQuery.ajax({
        url: baseUrl + "admin/mypage/staffIdList",
        headers: {
            'X-CSRF-Token': csrfToken
        },
        type: 'get',
        success: function (res) {
            if (res.staffIds) {
                var index;
                for (index = 0; index < res.staffIds.length; index++) {
                    var staffId = res.staffIds[index]['StaffID'];
                    var staffName = res.staffIds[index]['StaffName'];
                    jQuery("#select-login-staff-id").append('<option value="' + staffId + '">' + staffName + '</option>');
                    // Nhi: add staff list for salary detail of staff in month
                    jQuery("#select-staff-salary-id").append('<option value="' + staffId + '">' + staffName + '</option>');
                    jQuery("#staffId").append('<option value="' + staffId + '">' + staffName + '</option>');
                }
                utilsModule().hideLoadingModal();
            }
        },
        error: function (res) {
            console.log(res);
            utilsModule().hideLoadingModal();
        }
    });
}

function importSalary() {
    utilsModule().displayLoadingModal();
    var file = jQuery('#input-excel-file')[0].files[0];
    var fd = new FormData();
    fd.append('file', file);
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'admin/mypage/importSalary',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: fd,
        processData: false,  // tell jQuery not to process the data
        contentType: false,
    })
        .done(function (response) {
            response = JSON.parse(response);
            console.log(response)
            utilsModule().hideLoadingModal();
            jQuery("#update-lines-message").hide();
            jQuery("#add-lines-message").hide();
            jQuery("#error-lines-message").hide();
            jQuery("#error-lines-message-details").empty();
            if (parseInt(response.row_add) > 0) {
                jQuery("#add-lines-message").text(response.row_add + " records has been added.");
                jQuery("#add-lines-message").show();
            }
            if (parseInt(response.row_update) > 0) {
                jQuery("#update-lines-message").text(response.row_update + " records has been updated.");
                jQuery("#update-lines-message").show();
            }
            if (response.error) {
                swal(response.message, {
                    buttons: {
                        cancel: "OK",
                    },
                });

            }
            if (response.success) {
                swal({
                    text: "Success for Import",
                    icon: "success"
                });
            } else {
                swal({
                    text: "Import Error",
                    icon: "error"
                });
                if (parseInt(response.row_error) > 0) {
                    jQuery("#error-lines-message").text(response.row_error + " records got error.");
                    jQuery("#error-lines-message").show();
                    jQuery("#error-lines-message-details").empty();
                    var html = "";
                    jQuery.each(response.details_error, function (index, value) {
                        html += '<label id="error-lines-message-details" style="color:red;font-size:12px;">';
                        html += 'Details: Error line ' + value.line + ', ';
                        html += 'Reason: '
                        jQuery.each(value.message, function (index, value) {
                            html += value + '.';
                        });
                        html += '</label>';
                    });
                    jQuery("#error-lines-message-details").append(html);
                }
            }
        });
}

jQuery("#btn-login-staff").click(function () {
    sessionStorage.setItem("click-menu", '');
    window.open(
        baseUrl + 'admin/mypage/loginAsStaf/' + jQuery("#select-login-staff-id").val(),
        '_blank',
    );
});

/*
 * Reset Password
 */

(function ($) {
    var ResetPassword = function (element, options, index) {
        this.element = $(element);
        this.index = index;
        this.options = options;
        this.init();
    }

    ResetPassword.prototype = {
        init: function () {
            var _this = this;
            _this.setEvents();
        },

        setEvents: function () {
            var _this = this;
            _this.element.on("click", function () {
                utilsModule().displayLoadingModal();
                jQuery.ajax({
                    url: baseUrl + "admin/mypage/staffIdAll",
                    headers: {
                        'X-CSRF-Token': csrfToken
                    },
                    type: 'get',
                    success: function (res) {
                        if (res.staffIds) {
                            var index;
                            for (index = 0; index < res.staffIds.length; index++) {
                                var staffId = res.staffIds[index]['StaffID'];
                                var staffName = res.staffIds[index]['StaffName'];
                                jQuery("#reset-password-staff-id").append('<option value="' + staffId + '">' + staffName + '</option>');
                            }
                            $(_this.options.popup).modal();
                        }
                        utilsModule().hideLoadingModal();
                    },
                    error: function (res) {
                        console.log(res);
                        utilsModule().hideLoadingModal();
                    }
                });
            });
            $(_this.options.exportSubmit).on("click", function () {
                if (_this.validateExport()) {
                    utilsModule().displayLoadingModal();
                    _this.submit();
                }
            });
        },
        validateExport: function () {
            var _this = this;
            var isValidated = true;
            $(".field-error").remove();
            if ($("#reset-password-staff-id").val() == "") {
                $("#reset-password-staff-id").parent().append(_this.getMessageError("Staff ID field is required"));
                isValidated = false;
            }
            return isValidated;
        },

        getMessageError: function (message) {
            var messageError = $("<div class='field-error' style='color:red'></div>");
            messageError.html(message);
            return messageError;
        },

        submit: function () {
            var _this = this;
            $.ajax({
                url: _this.options.urlPost,
                headers: {
                    'X-CSRF-Token': csrfToken
                },
                type: 'post',
                data: {
                    staff_id: $("#reset-password-staff-id").val()
                },
                success: function (res) {
                    utilsModule().hideLoadingModal();
                    if (res.status) {
                        swal('Reset password completed! Password is: 123456');
                        return;
                    }
                    swal('Error', res.message, 'error');
                },
                error: function (res) {
                    swal('Error', 'An error occurred, please try again', 'error');
                    utilsModule().hideLoadingModal();
                }
            });
        }
    }

    $.fn.ResetPassword = function (options) {
        var index = 0;
        return this.each(function () {
            new ResetPassword(this, options, index);
            index++;
        });
    };
})(jQuery);

jQuery(document).ready(function () {
    jQuery('#btn-reset-password-staff').ResetPassword({
        popup: "#reset-password-modal",
        exportSubmit: "#btn-reset-password-submit",
        urlPost: baseUrl + 'admin/mypage/reset-password'
    });
});

function requestExportOTAll() {
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'admin/mypage/exportOTAll',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: {
            date: jQuery("#input-ot-date").val(),
        }
    })
        .done(function (response) {
            utilsModule().hideLoadingModal();
            if (response.success) {
                var file = response.file;
                location.href = baseUrl + file;
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
function requestExportOTSummaryAll() {
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'admin/mypage/exportOTSumarryAll',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: {
            date: jQuery("#input-ot-summary-date").val(),
        }
    })
        .done(function (response) {
            utilsModule().hideLoadingModal();
            if (response.success) {
                var file = response.file;
                location.href = baseUrl + file;
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


