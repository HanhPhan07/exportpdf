$(document).ready(function () {
    window.onload = date_time('date_time');
});
$(document).ready(function () {
    leaveFormModule.initForm()
});
var leaveFormModule = (function () {

    var initForm = function () {
        initEventListener();
    }
    var initEventListener = function () {

        $('.radio-reject').click(function (event) {
            if ($(this).prop("checked") === true) {
                _showReasonReject($(this).attr('inforid'), $(this));
            }
        });

    }

    var _showReasonReject = function (inforID, radio) {
        swal("Please Fill Out Reject Reason", {
            content: {
                element: "input"
            },
            buttons: {
                cancel: "Cancel",
                confirm: {
                    text: "OK",
                    value: "catch",
                }
            },
            className: 'swal-wide-al',
        })
            .then(function (value) {
                if (value == '') {
                    _showReasonReject();
                } else if (value == null) {
                    radio.prop("checked", false);
                    return;
                } else {
                    $('#reason_reject_' + inforID).val(value)
                }
            });
    }
    return {
        initForm: initForm
    }
})()
