$(document).ready(function () {
    shiftListOfStaffFormModule.initForm()
});

var shiftListOfStaffFormModule = (function () {
    var initForm = function () {
        initEventListener();
    }
    var initEventListener = function () {
       
        $( '#list-result' ).on( 'click', '.previewApprovedButton', function () {
       
            $('.lab-StaffID').html($(this).attr('data-StaffID'));
            $('.lab-Name').html($(this).attr('data-Name'));
            $('.lab-Shift').html($(this).attr('data-Shift'));
            $('.lab-witchStaffID').html($(this).attr('data-witchStaffID'));
            $('.lab-witchName').html($(this).attr('data-witchName'));
            $('.lab-witchShift').html($(this).attr('data-witchShift'));
            $('.lab-reason').html($(this).attr('data-reason-preview'));
            $('#shiftchangeid').val($(this).attr('data-shiftchangeid'));
            $('#witchOpt').val($(this).attr('data-witchOpt'));

            $('.fromDate-preview').html($(this).attr('data-fromDate-preview'));
            $('.toDate-preview').html($(this).attr('data-toDate-preview'));

            $('#previewApprovedModal').modal('toggle');
        });
        
        $('.submitApproved').click(function (event) {
            $('#previewApprovedModal').modal('toggle');
            console.log($(this).attr('data-val'))
            if( $(this).attr('data-val') == 1){
                _showReasonReject();
            }else{
                _submitApproved();
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
                    return;
                } else {
                    _submitApproved(value,0)
                }
            });
    }
    var _submitApproved = function(reject='',approve=1) {
        var params = [];

        params.push({name: 'shiftID', value: $('#shiftchangeid').val()})
        params.push({name: 'StaffName', value: $('.lab-witchName').text()})
        params.push({name: 'leaderApproved', value: approve})
        params.push({name: 'leaderReasonReject', value: reject})
        jQuery.ajax({
            type: 'post',
            url: __baseUrl + 'shift/leaderConfirm',
            headers: {'X-CSRF-TOKEN': csrfToken},
            data: params
        })
        .done(function (response) {
            //console.log(response)
            //$('#row-'+$('#shiftchangeid').val()).hide();
            if (response.success) {
                _reloadList();
                swal({
                    text: "Success for updated",
                    icon: "success",
                });
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
    var _reloadList = function(){
        var url = baseUrl + 'leader/shiftform';
        var params = $('#shiftSearch').serializeArray();
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
        initForm: initForm
    }

})()

jQuery(document).ready(function() {
    var datepickerId = "#DateFrom";
    jQuery(datepickerId).monthpicker({
        monthNames: ["/01", "/02", "/03", "/04", "/05", "/06", "/07", "/08", "/09", "/10", "/11", "/12"],
        showOn:     "both",
        buttonImage: baseUrl+"img/calendar1.png",
        buttonImageOnly: true,
        showButtonPanel: false,
        changeYear: false,
        yearRange: 'c-2:c+2',
        dateFormat: 'yyMM'
    });
    $("#DateFrom").change(function () {
        $('#shiftSearch').submit()
    }) 
});
