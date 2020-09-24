/*
 * Staff Export
 */

(function ($) {
    var staffExport = function (element, options, index) {
        this.element = $(element);
        this.index = index;
        this.options = options;
        this.dateFromElement = $(options.dateFrom);
        this.dateToElement = $(options.dateTo);
        this.staffIdElement = $(options.staffId);
        this.init();
    }

    staffExport.prototype = {
        init: function () {
            var _this = this;
            _this.dateFromElement = $(_this.options.dateFrom);
            _this.dateToElement = $(_this.options.dateTo);
            _this.staffIdElement = $(_this.options.staffId);
            _this.setEvents();
        },

        setEvents: function () {
            var _this = this;
            _this.element.on("click", function () {
                $(_this.options.popup).modal();
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
            if (_this.dateFromElement.val() == "") {
                _this.dateFromElement.parent().append(_this.getMessageError("Date From field is required"));
                isValidated = false;
            }

            if (_this.dateToElement.val() == "") {
                _this.dateToElement.parent().append(_this.getMessageError("Date To field is required"));
                isValidated = false;
            }

            if (_this.staffIdElement.val() == "") {
                _this.staffIdElement.parent().append(_this.getMessageError("Staff ID field is required"));
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
                    staff_id: _this.staffIdElement.val(),
                    date_from: _this.dateFromElement.val(),
                    date_to: _this.dateToElement.val(),
                },
                success: function (res) {
                    if (res.error == "") {
                        window.location.href = baseUrl + res.file;
                    }
                    utilsModule().hideLoadingModal();
                },
                error: function (res) {
                    utilsModule().hideLoadingModal();
                }
            });
        }
    }

    $.fn.staffExport = function (options) {
        var index = 0;
        return this.each(function () {
            new staffExport(this, options, index);
            index++;
        });
    };
})(jQuery);

// Initialize Staff Export

$(function () {
    jQuery('#staff-export-overtime').click(function () {
        showStaffListToSelect()
        jQuery("#export-overtime-modal").modal();
    })
    jQuery('#btn-staff-export-overtime').click(function () {
        utilsModule().displayLoadingModal();
        jQuery.ajax({
            type: 'post',
            url: baseUrl + 'admin/mypage/exportOvertime',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: {
                staff_id: jQuery("#staffId").val(),
                date_from: jQuery('#dateFrom').val(),
                date_to: jQuery('#dateTo').val()
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
    })
    // jQuery('#staff-export-overtime').staffExport({
    //     popup: "#export-overtime-modal",
    //     exportSubmit: "#btn-staff-export-overtime",
    //     dateFrom: "#dateFrom",
    //     dateTo: "#dateTo",
    //     staffId: "#staffId",
    //     urlPost: baseUrl + 'admin/mypage/exportOvertime'
    // });
    dateRangePickerModule.dateRange("#dateFrom", "#dateTo");
});

/*
 * Shift Leave Export
 */

(function ($) {
    var shiftLeaveExport = function (element, options, index) {
        this.element = $(element);
        this.index = index;
        this.options = options;
        this.month = $(options.month);
        this.init();
    }

    shiftLeaveExport.prototype = {
        init: function () {
            var _this = this;
            _this.month = $(_this.options.month);
            _this.setEvents();
        },

        setEvents: function () {
            var _this = this;
            _this.element.on("click", function () {
                $(_this.options.popup).modal();
            });
            $(_this.options.exportSubmit).on("click", function () {
                if (_this.validateExport()) {
                    utilsModule().displayLoadingModal();
                    _this.submit(0);
                }
            });

            $(_this.options.downloadCSVbutton).on("click", function () {
                if (_this.validateExport()) {
                    utilsModule().displayLoadingModal();
                    _this.submit(1);
                }
            });

            $(_this.options.exportPdf).on("click", function () {
                if (_this.validateExport()) {
                    _this.submit(2);
                }
            });
        },
        validateExport: function () {
            var _this = this;
            var isValidated = true;
            $(".field-error").remove();
            if (_this.month.val() == "") {
                _this.month.parent().append(_this.getMessageError("Month field is required"));
                isValidated = false;
            }
            return isValidated;
        },

        getMessageError: function (message) {
            var messageError = $("<div class='field-error' style='color:red'></div>");
            messageError.html(message);
            return messageError;
        },
        submit: function (type) {
            // Type: 0 download excel file, 1: download csv
            var _this = this;
            if (type == 2) {
                window.open(_this.options.urlPdf + '?month=' + _this.month.val(), '_blank');
                return;
            }
            $.ajax({
                url: _this.options.urlPost,
                headers: {
                    'X-CSRF-Token': csrfToken
                },
                type: 'post',
                data: {
                    month: _this.month.val(),
                    type: type
                },
                success: function (res) {
                    console.log(res);
                    if (res.error == "") {
                        window.location.href = baseUrl + res.file;
                    }
                    utilsModule().hideLoadingModal();
                },
                error: function (res) {
                    console.log(res);
                    utilsModule().hideLoadingModal();
                }
            });
        }
    }

    $.fn.shiftLeaveExport = function (options) {
        var index = 0;
        return this.each(function () {
            new shiftLeaveExport(this, options, index);
            index++;
        });
    };
})(jQuery);

jQuery(document).ready(function () {

    var datepickerId = "#dateShiftLeave";
    jQuery(datepickerId).monthpicker({
        monthNames: ["/01", "/02", "/03", "/04", "/05", "/06", "/07", "/08", "/09", "/10", "/11", "/12"],
        showOn: "both",
        buttonImage: baseUrl + "img/calendar1.png",
        buttonImageOnly: true,
        showButtonPanel: false,
        changeYear: false,
        yearRange: 'c-2:c+2',
        dateFormat: 'yyMM',
        maxDate: new Date()
    });
    jQuery('body').on('propertychange input', datepickerId, function () {
        jQuery(datepickerId).val(jQuery(datepickerId).val().replace(/[^\d\/]+/g, ''))
    });

    jQuery('#shift-leave').shiftLeaveExport({
        popup: "#export-shift-leave-modal",
        exportSubmit: "#btn-staff-export-shift-leave",
        exportPdf: "#btn-staff-export-pdf-shift-leave",
        downloadCSVbutton: "#btn-download-csv-shift-leave",
        month: "#dateShiftLeave",
        urlPost: baseUrl + 'admin/mypage/exportShiftLeave',
        urlPdf: baseUrl + 'admin/mypage/exportShiftLeavePdf'
    });
});


// Initialize Staff Export

$(function () {
    jQuery('#staff-info-export').staffExport({
        staffId: "#staffId",
        urlPost: baseUrl + 'admin/mypage/exportOvertime'
    });
    dateRangePickerModule.dateRange("#dateFrom", "#dateTo");
});

/*
 * Shift Leave Export
 */

(function ($) {
    var StaffInfoExport = function (options) {
        this.options = options;
        this.init();
    }
    StaffInfoExport.prototype = {
        init: function () {
            var _this = this;
            _this.setEvents();
        },
        setEvents: function () {
            var _this = this;
            $(_this.options.exportSubmit).on("click", function () {
                _this.submit();
            });
        },
        submit: function () {
            var _this = this;
            utilsModule().displayLoadingModal();
            $.ajax({
                url: _this.options.urlPost,
                headers: {
                    'X-CSRF-Token': csrfToken
                },
                type: 'post',
                async: true,
                success: function (res) {
                    if (res.error == "") {
                        window.location.href = baseUrl + res.file;
                    }
                    utilsModule().hideLoadingModal();
                },
                error: function (res) {
                    console.log(res);
                    utilsModule().hideLoadingModal();
                }
            });
        }
    }
    new StaffInfoExport({ exportSubmit: '#btn-staff-info-export', urlPost: baseUrl + 'admin/mypage/exportStaffInfo' });
})(jQuery);

/*
 * Staff overtime aggregation export pdf
 */

(function ($) {
    var OvertimeAggregation = function (options) {
        this.options = options;
        this.dateFrom = $(this.options.dateFrom);
        this.dateTo = $(this.options.dateTo);
        this.weekNo = $(this.options.weekNo);
        this.init();
    }
    OvertimeAggregation.prototype = {
        init: function () {
            var _this = this;
            _this.setEvents();
        },
        setEvents: function () {
            var _this = this;
            $(_this.options.exportSubmit).on("click", function () {
                if (_this.validateExport()) {
                    _this.submit();
                }
            });

            $(_this.options.btnShowMoal).on("click", function () {
                $(_this.options.modal).modal();
            })
        },
        validateExport: function () {
            var _this = this;
            var isValidated = true;
            $(".field-error").remove();
            if (_this.dateFrom.val() == "") {
                _this.dateFrom.parent().append(_this.getMessageError("Date From field is required"));
                isValidated = false;
            }
            if (_this.dateTo.val() == "") {
                _this.dateTo.parent().append(_this.getMessageError("Date To field is required"));
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
            utilsModule().displayLoadingModal();
            window.location.href = _this.options.urlPost + '?dateFrom=' + _this.dateFrom.val() + '&dateTo=' + _this.dateTo.val() + '&weekNo=' + _this.weekNo.val();
            setTimeout(function () {
                utilsModule().hideLoadingModal();
            }, 3000);
        }
    }
    new OvertimeAggregation({
        exportSubmit: '#btn-staff-export-overtime-aggregation',
        modal: '#export-overtime-aggregation-modal',
        btnShowMoal: '#btn-export-overtime-aggregation',
        dateFrom: '#dateFromOvertimeAggregation',
        dateTo: '#dateToOvertimeAggregation',
        weekNo: '#weekNoOvertimeAggregation',
        urlPost: baseUrl + 'admin/mypage/exportOvertimeAggregation'
    });

    var datepickerId = '#dateFromOvertimeAggregation';
    jQuery(datepickerId).datepicker({
        onSelect: function () {
            var dateFrom = new Date(jQuery("#dateFromOvertimeAggregation").val());
            var month = parseInt(dateFrom.getMonth()) + 1;
            var d = new Date(dateFrom.getFullYear(), month, 0);
            var selectedDate = dateFrom.getDate();
            var lastDay = d.getDate();
            var lastDayOfMonth = d.getDate();
            for (var i = selectedDate; i <= lastDay; i++) {
                var t = new Date(dateFrom.getFullYear(), dateFrom.getMonth(), i);
                if (getDayName(t) == "Sunday") {
                    lastDay = i;
                    break;
                }
            }
            var weekNo = 1;
            for (var i = 1; i <= lastDayOfMonth; i++) {
                var t = new Date(dateFrom.getFullYear(), dateFrom.getMonth(), i);
                if (i == selectedDate) {
                    break;
                }
                if (getDayName(t) == "Sunday") {
                    weekNo += 1;
                }
            }
            month = month + '';
            if (month.length < 2) {
                month = '0' + month;
            }
            lastDay = lastDay + '';
            if (lastDay.length < 2) {
                lastDay = '0' + lastDay;
            }
            jQuery("#dateToOvertimeAggregation").val(dateFrom.getFullYear() + "/" + month + "/" + lastDay);
            jQuery("#weekNoOvertimeAggregation").val(weekNo);
        },
    });
    jQuery(datepickerId).datepicker("option", "showOn", 'both');
    jQuery(datepickerId).datepicker("option", "buttonImageOnly", true);
    jQuery(datepickerId).datepicker("option", "buttonImage", baseUrl + '/img/calendar.png');
    jQuery(datepickerId).datepicker("option", "dateFormat", "yy/mm/dd");

    function getDayName(d) {
        var weekday = new Array(7);
        weekday[0] = "Sunday";
        weekday[1] = "Monday";
        weekday[2] = "Tuesday";
        weekday[3] = "Wednesday";
        weekday[4] = "Thursday";
        weekday[5] = "Friday";
        weekday[6] = "Saturday";
        var n = weekday[d.getDay()];
        return n;
    }
})(jQuery);



