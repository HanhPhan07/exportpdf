$(document).ready(function () {
        
    var currentView = $("#view").val();
    if (currentView == "stafflists" ) {
        shiftListOfStaffFormModule.initForm()
    }else{
         shiftFormModule.initForm()
    }
});

var shiftListOfStaffFormModule = (function () {
    var datePickers = ["#fromDate", "#toDate"]
    var initForm = function () {
        datepickerModule.initial(datePickers, true);
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
                    _submitReject(value)
                }
            });
    }
    var _submitApproved = function() {
        var params = [];

        params.push({name: 'shiftID', value: $('#shiftchangeid').val()})
        params.push({name: 'StaffName', value: $('.lab-witchName').text()})
        params.push({name: 'StaffApproval', value: 1})
        jQuery.ajax({
            type: 'post',
            url: __baseUrl + 'shift/StaffApproved',
            headers: {'X-CSRF-TOKEN': csrfToken},
            data: params
        })
        .done(function (response) {
            //console.log(response)
            //$('#row-'+$('#shiftchangeid').val()).hide();
            if (response.success) {
                _reloadList()
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
    var _submitReject = function(reject) {
        var params = [];

        params.push({name: 'shiftID', value: $('#shiftchangeid').val()})
        params.push({name: 'StaffName', value: $('.lab-witchName').text()})
        params.push({name: 'StaffApproval', value: 0})
        params.push({name: 'StaffReasonReject', value: reject})
        jQuery.ajax({
            type: 'post',
            url: __baseUrl + 'shift/StaffReject',
            headers: {'X-CSRF-TOKEN': csrfToken},
            data: params
        })
        .done(function (response) {
           // console.log(response)
            if (response.success) {
              //  $('#row-'+$('#shiftchangeid').val()).hide();
                _reloadList()
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
        var url = baseUrl + 'shift/stafflists';
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

var shiftFormModule = (function () {
    var datePickers = ["#fromDate", "#toDate"]
    var initForm = function () {
        datepickerModule.initial(datePickers, true);
        initEventListener();
        _generateShiftTo($('#Shift').val())
    }
    var initEventListener = function () {
        $("#fromDate").change(function () {
            $('#toDate').datepicker("setDate", $('#fromDate').val());
        })
        $("#reason").change(function () {
            $('.reason-preview').html($('#reason').val());
        })
        $('.switch-act').change(function(){
            if (_checkRequired() == true) {
                _updShift(true);
            }
        });
        $('.switch-act-date').change(function(){
            if (_checkRequired() == true) {
                _updShift(false); //case switch staff can be null
            }
        });
        $("#previewButton").click(function (event) {
            if (_checkReviewRequired() == false) {
                swal("Please fill out all fields", {
                    buttons: {
                        cancel: "OK",
                    },
                });
                return;
            }
            _generatePreview();
            $('#previewModal').modal('toggle');
        });
        if($("#isAdmin").val() == "1"){
        }else{            
            _setStartDate();
        }
        $('#submitFromPreview').click(function (event) {
            $('#previewModal').modal('toggle');
            jQuery.ajax({
                type: 'post',
                url: __baseUrl + '/shift',
                headers: {'X-CSRF-TOKEN': csrfToken},
                data: $('#form-shift').serializeArray()
            })
                .done(function (response) {
                    if (response.success) {
                        $('#form-shift')[0].reset();
                        swal({
                            text: "Your application form for shift change has been submitted successfully",
                            icon: "success",
                        });
                        return;
                    } else {
                        console.log(response.error);
                        swal(response.error, {
                            buttons: {
                                cancel: "OK",
                            },
                        });
                        return;
                    }
                })
                
                .fail(function (response) {
                    console.log(response);
                });
        });
        
        $('.submitApproved').click(function (event) {
            $('#previewApprovedModal').modal('toggle');
            var params = [];
            params.push({name: 'shift', value: $('#shiftchangeid').val()})
            params.push({name: 'witchOpt', value: $('#witchOpt').val()})
            params.push({name: 'approved', value: $(this).attr('data-val')})
            jQuery.ajax({
                type: 'post',
                url: __baseUrl + 'shift/approved',
                headers: {'X-CSRF-TOKEN': csrfToken},
                data: params
            })
                .done(function (response) {
                    if (response.success) {
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
        });
    }
    var _updShift = function(readonly) {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var switchStaffID = $('#switchStaffID').val();
        var StaffID = $('#StaffID').val();
        $('#shift_change').html('<input type="text" name="switchShift" class="form-control text-center" placeholder="Shift" id="switchShift">')
        if (switchStaffID == StaffID) {
            $("#switchShift").attr("readonly", false);

        }
        else if(switchStaffID == ''){
            $("#switchShift").attr("readonly", false);
            _generateShiftTo($('#Shift').val())
        }else{
            $("#switchShift").attr("readonly", readonly); //case switch staff can be null

        }

        jQuery.ajax({
            type: 'post',
            url: __baseUrl + 'shift/getShift',
            headers: {'X-CSRF-TOKEN': csrfToken},
            data:{'fromDate': fromDate, 'toDate' : toDate, 'switchStaffID' : switchStaffID}
        })
            .done(function (response) {
                console.log(response)
                if($("#isAdmin").val() == "1"){
                    var staffShifts = response.staffShifts.shift;
                    var switchShifts = response.switchShifts.shift;
                    var switchName = response.switchShifts.name;

                    $('#Shift').val(staffShifts);
                    $('.lab-shift').html(staffShifts);

                    $('#switchShift').val(switchShifts);
                    $('.lab-witchShift').html(switchShifts);

                    $('#switchName').val(switchName);
                    $('.lab-witchName').html(switchName);

                    $('.lab-witchStaffID').html(switchStaffID);
                    $('.fromDate-preview').html(fromDate);
                    $('.toDate-preview').html(toDate);
                    if(switchStaffID == ''){
                        _generateShiftTo($('#Shift').val())
                    }
                }else{       
                    if(!response.msg){
                        swal('Please try other day', {
                                buttons: {
                                    cancel: "OK",
                                },
                            });
                            return;
                    }
                    else{
                        var staffShifts = response.staffShifts.shift;
                        var switchShifts = response.switchShifts.shift;
                        var switchName = response.switchShifts.name;

                        $('#Shift').val(staffShifts);
                        $('.lab-shift').html(staffShifts);

                        $('#switchShift').val(switchShifts);
                        $('.lab-witchShift').html(switchShifts);

                        $('#switchName').val(switchName);
                        $('.lab-witchName').html(switchName);

                        $('.lab-witchStaffID').html(switchStaffID);
                        $('.fromDate-preview').html(fromDate);
                        $('.toDate-preview').html(toDate);
                        if(switchStaffID == ''){
                            _generateShiftTo($('#Shift').val())
                        }
                    }
                }
            });
    }
    var _checkRequired = function () {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        return fromDate != '' && toDate != '';
    }
    var _checkReviewRequired = function () {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var switchStaffID = $('#switchStaffID').val();
        var reason = $('#reason').val();
        var switchShift =  $('#switchShift').val();
        return fromDate != '' && toDate != '' && switchShift != '' && reason != '';
    }
    var _setStartDate = function(date){   
        var date = $('#fromDate').val();     
        $('#fromDate').datepicker('setStartDate',date);
        $('#toDate').datepicker('setStartDate',date);        
    }
    var _generatePreview = function(){
        var switchShifts = $('#switchShift').val();
        var switchName = $('#switchName').val();
        var switchStaffID = $('#switchStaffID').val();

        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val(); 

        $('.lab-witchShift').html(switchShifts);
        $('.lab-witchName').html(switchName);
        $('.lab-witchStaffID').html(switchStaffID);

        $('.fromDate-preview').html(fromDate);
        $('.toDate-preview').html(toDate);
    }
    var _generateShiftTo = function(currentShift){
        options = [{value: 1,text: 1},{value: 2,text:2},{value: 3,text:3}];        
        var html = '<select name="switchShift" class="form-control" id="switchShift" >';
        $.each(options, function(index, option) {
            if(option.value != currentShift) html += "<option value='"+option.value+"'>"+option.text+"</option>"
        })
        html += '</select>';
        $('#shift_change').html(html)
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

