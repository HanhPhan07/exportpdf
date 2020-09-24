$(document).ready(function () {
    window.onload = date_time('date_time');
});
$(document).ready(function () {
    leaveFormModule.initForm()
});
var leaveFormModule = (function () {
    var direction = '';
    var sort_name = '';
    var initForm = function () {
        var datePickers = ["#fromDate"]
        initEventListener();
        datepickerModule.initial(datePickers,true);
        $( '#list-result' ).on( 'click', '.radio-approval', function () {
            const _this = this;
            const parentContainer = $(this).closest(".middle_td");
            const currentState = $(this).prop("checked");
            const selectedData = $(this).data("selected");
            const checkedBoxes = $(parentContainer).find("input[type='radio']:checked");
            if (checkedBoxes.length === 1 && currentState && selectedData == 1) {
                $(checkedBoxes).data("selected", 0);
                $(_this).prop("checked", false);
            } 
        });
        // $(".radio-approval").on('click', function(e) {
        //      const _this = this;
        //     const parentContainer = $(this).closest(".middle_td");
        //     const currentState = $(this).prop("checked");
        //     const selectedData = $(this).data("selected");
        //     const checkedBoxes = $(parentContainer).find("input[type='radio']:checked");
        //     if (checkedBoxes.length === 1 && currentState && selectedData == 1) {
        //         $(checkedBoxes).data("selected", 0);
        //         $(_this).prop("checked", false);
        //     } 
           
        // })
        $( '#list-result' ).on( 'change', '.radio-approval', function () {
            const parentContainer = $(this).closest(".middle_td");
            const checkedBoxes = $(parentContainer).find("input[type='radio']:checked");
            $(checkedBoxes).data("selected", 0);
            $(this).data("selected", 1);
        });
        // $(".radio-approval").on("change", function() {
        //     const parentContainer = $(this).closest(".middle_td");
        //     const checkedBoxes = $(parentContainer).find("input[type='radio']:checked");
        //     $(checkedBoxes).data("selected", 0);
        //     $(this).data("selected", 1);
        // })
    }
   
    var initEventListener = function () {
        $( '#list-result' ).on( 'change', '.radio-reject', function () {
           if ($(this).prop("checked") === true) {
                _showReasonReject($(this).attr('inforid'), $(this));
            }
        });
        // $('.radio-reject').click(function (event) {
        //     if ($(this).prop("checked") === true) {
        //         _showReasonReject($(this).attr('inforid'), $(this));
        //     }
        // });
        $(document).on("change", "#fromDate", function () { 
            $('#setDate').val(1)
            $('#leaveSearch').submit(); 

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
                    $('#'+inforID+'-leave-accept-0').prop("checked", false);
                    _showReasonReject();
                } else if (value == null) {
                    $('#'+inforID+'-leave-accept-0').prop("checked", false);
                    return;
                } else {
                    $('#reason_reject_' + inforID).val(value)
                }
            });
    }
    return {
        initForm: initForm,
        touch: touch
    }
})()
 var touch = function (els){
        var data_goto = els.attr('data-goto');
            $('.direction-img').show();
            if (data_goto == 'asc') data_goto = 'desc';
            else if (data_goto == 'desc') data_goto = 'asc';
            els.attr('data-goto', data_goto);

            var char = '▲';
            if (data_goto == 'desc') char = '▼';
            els.closest('tr').find('.direction-char').hide();
            $(this).closest('tr').find('.direction-img').show();

            els.find('.direction-img').hide();
            els.find('.direction-char').html(char);
            els.find('.direction-char').show();

            sort_name = els.attr('data-label');
            direction = data_goto;
            showPage();
    }
    var  showPage = function() {
        var url = baseUrl + 'leader/leaveform';
        var params = $('#leaveSearch').serializeArray();
        params.push({name: 'direction', value: direction});
        params.push({name: 'sort', value: sort_name});
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