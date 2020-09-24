$(document).ready(function () {
    $('#show-salary-details-user-modal').on('click', function (e) {
        e.preventDefault();
        $('#popup-salary-details-user-modal').modal();
    });

    /* export salary details */
    $('#export-salary-details-user').on('click', function (e) {
        e.preventDefault();
        swal("Please wait! Loading...", {
            buttons: false,
        });

        $.ajax({
            type: 'post',
            url: baseUrl + 'salary/details',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: {
                staff_id: $('#staff_id').val(),
                date: $('#input-salary-details-date').val(),
                export_mode: 'PDF'
            },
            success: function (response) {
                if (response.pdf) {
                    window.open(
                        baseUrl + response.pdf,
                        '_blank',
                    )
                    swal.close();
                } else {
                    swal(response.message, {
                        buttons: {
                            cancel: "OK",
                        },
                    });
                    return;
                }
            },
            error: function () {
                console.log("error")
                swal("Have an error. Please try again !", {
                    buttons: false,
                });
            }
        });
    });

    /* sort table notification --vers-desktop*/
    $('.th-sort').on('click', function () {
        $column = $(this).attr('col');
        if ($(this).attr('arrow') == 'not' || $(this).attr('arrow') == 'down') {
            $arrow = 'ASC';
        } else {
            $arrow = 'DESC';
        }

        location.href = baseUrl + 'notification/index/col-' + $column + '/sort-' + $arrow;
    });

    /* sort table notification --ver-mobile */
    $('#applySort').on('click', function () {
        $column = $('#option-col').val();
        $arrow = $('#option-col').children("option:selected").data('arrow');
        location.href = baseUrl + 'notification/index/col-' + $column + '/sort-' + $arrow;
    });

    var datepickerId = "#dateShiftLeave";

    $(datepickerId)
        .monthpicker({
            monthNames: ["/01", "/02", "/03", "/04", "/05", "/06", "/07", "/08", "/09", "/10", "/11", "/12"],
            showOn:     "both",
            buttonImage: baseUrl+"img/calendar1.png",
            buttonImageOnly: true,
            showButtonPanel: false,
            changeYear: false,
            yearRange: 'c-2:c+2',
            dateFormat: 'yyMM',
            maxDate: new Date()
        })
        .on("click", function () {
            optimizePositionDatePicker('dateShiftLeave');
        });

    $(window).resize(function () {
        $("#ui-monthpicker-div").css("display", "none");
    });
    function getPosition(element) {
        var rect = element.getBoundingClientRect();
        return { x: rect.left, y: rect.top };
    }
    function optimizePositionDatePicker(str_id_datepicker) {
        var element = document.getElementById(str_id_datepicker);
        var pos = getPosition(element);
        $("#ui-monthpicker-div").css("left", pos.x);
        $("#ui-monthpicker-div").css("top", pos.y + 40);
        $("#ui-monthpicker-div").css("display", "block");
    }

    $('body').on('propertychange input', datepickerId, function() {
        $(datepickerId).val($(datepickerId).val().replace(/[^\d\/]+/g,''))
    });

    $('#shift-leave').shiftLeaveExport({
        popup: "#export-shift-leave-modal",
        exportSubmit: "#btn-staff-export-shift-leave",
        exportPdf: "#btn-staff-export-pdf-shift-leave",
        downloadCSVbutton: "#btn-download-csv-shift-leave",
        month: "#dateShiftLeave",
        urlPost: baseUrl + 'mypage/exportShiftLeave',
        urlPdf: baseUrl + 'mypage/exportShiftLeavePdf'
    });
});

function beforeRenderPage() {
    /* Page notification */
    if ($('#notiTable').length) {
        var url_string = window.location.href;

        if (url_string.indexOf('/sort-')) { /* check url contain sort */
            var col = url_string.split("/col-")[1];
            var sort = url_string.split("/sort-")[1];
            col = col.substr(0, col.indexOf('/'));

            /* vers-desktop */
            $('#notiTable th').each(function (index) {
                if ($(this).attr('col') == col) {
                    if (sort == "ASC") {
                        $(this).find('.arrow').html('&ensp;<i class="fas fa-chevron-circle-up"></i>');
                        $(this).attr('arrow', 'up');
                    } else {
                        $(this).find('.arrow').html('&ensp;<i class="fas fa-chevron-circle-down"></i>');
                        $(this).attr('arrow', 'down');
                    }
                }
            });

            /* vers-mobile */
            // var optionCol = document.getElementById("option-col").value;
            // var optionArrow = document.getElementById("option-col").children("option").data('arrow');
            $('#option-col option').each(function (index) {
                if ($(this).val() == col && $(this).data('arrow') == sort) {
                    $(this).attr('selected', 'selected');
                }
            });
        }
    }

    /**
     * Author:Quang
     * Date:2020/06/17
     * Function:check user watched news or not
     */
    if($('#show_popup_news').length){
        showNotiNews()
    }
}

window.onload = beforeRenderPage();

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

/**
 * Author:Quang
 * Date:2020/06/17
 * Function:show popup alert have news
 */
function showNotiNews() {
    swal({
        title: 'You have some news!',
        buttons:{
            cancel: false,
            confirm: "Read"
        }
      })
        .then((watchNews) => {
                if (watchNews) {
                    $('#modalWhatsNews').modal()
                }
        })
}
