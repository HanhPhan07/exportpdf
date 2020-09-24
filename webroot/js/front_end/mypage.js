$( document ).ready(function() {
    window.onload = date_time('date_time');

    var currentView = $("#view").val();
    if (currentView == "plform" || currentView == "ulform") {
        alFormModule.initForm()
    }

    if (currentView == "otform") {
        otFormModule.initForm()
    }
    if (currentView == "score") {
        scoreFormModule.initForm()
    }
    if (currentView == "paidleave") {
        paidleaveModule.initForm()
    }
    if (currentView == "unpaidleave") {
        paidleaveModule.initForm()
    }
    if (currentView == "companybasicrules") {
        companybasicrulesModule.initForm()
    }
    // nhi: show datepicker not out of the box - 20200413
    jQuery(window).resize(function() {
        var window_width = jQuery(window).width();
        var left_datepicker = window_width/2 - 150;
        jQuery("#ui-monthpicker-div").css("left",left_datepicker);
        jQuery("#ui-datepicker-div").css("left",left_datepicker);
    });
    // end

    jQuery("#staff-emerg").click(function (event) {
        requestExportEmerg();
    });

    jQuery(".readMore").click(function(evt){
        var news = $(this).closest(".news")
        news.find(".news-body").addClass("hide_el")
        news.find(".news-body-full").removeClass("hide_el")
        news.find(".readMore").addClass("hide_el")
        news.find(".readLess").removeClass("hide_el")
    });

    jQuery(".readLess").click(function(evt){
        var news = $(this).closest(".news")
        news.find(".news-body").removeClass("hide_el")
        news.find(".news-body-full").addClass("hide_el")
        news.find(".readMore").removeClass("hide_el")
        news.find(".readLess").addClass("hide_el")
    });

    jQuery('.readMoreInPopup').click(function(e){
        var news = $(this).closest(".news")
        news.removeClass('see-in-popup')
        news.addClass('readed')
        news.find(".news-body").addClass("hide_el")
        news.find(".news-body-full").removeClass("hide_el")
        news.find(".readMoreInPopup").addClass("hide_el")
        news.find(".readLessInPopup").removeClass("hide_el")
        var id_news = news.find('.id_news').val()
        jQuery.ajax({
                    headers: {'X-CSRF-TOKEN': csrfToken},
                    type: 'post',
                    data:{'id_news': id_news},
                    url: baseUrl + '/mypage/addWatched',
                }).done(function(response) {
                    console.log(response)
                });
    });

    jQuery(".readLessInPopup").click(function(evt){
        var news = $(this).closest(".news")
        news.find(".news-body").removeClass("hide_el")
        news.find(".news-body-full").addClass("hide_el")
        news.find(".readMoreInPopup").removeClass("hide_el")
        news.find(".readLessInPopup").addClass("hide_el")
    });
});

$(document).on("click","#label-user",function() {
    $(".basic-info").toggleClass("hide");
})

var otFormModule = (function() {
    var shifts = {0:"0",1: "1", 2: "2", 3: "3", 4:"4", 8: "Weekend",5:"Vietnamese Holiday",6:"Tet Holiday"};
    var shouldDisplayFromTimePicker = true;
    var isWeekend = false;
    var datePickers = ["#date"];
    var _shiftAllowedToOT = [0, 2, 3];
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
    var validateInputs = [
        {
            selector: "input[name='Date']",
            type: 'required',
            label: 'Date'
        },
        {
            selector: "input[name='Shift']",
            type: 'required',
            label: 'Shift'
        },
        {
            selector: "input[name='FromTime']",
            type: 'required',
            label: 'FromTime'
        },
        {
            selector: "input[name='ToTime']",
            type: 'required',
            label: 'ToTime'
        },
        {
            selector: "input[name='Total']",
            type: 'required',
            label: 'Total (mins)'
        }
    ]

    var isAdmin = $('#isAdmin').val() === "true" ? true : false
    var todayVNHoliday = $('#todayVNHoliday').val() != "" ? true : false

    var initForm = function(reinitial = false) {
        var date = (_serverTime) ? new Date(_serverTime) : new Date();
        var day = date.getDay();
        isWeekend = (day === 6) || (day === 0);
        _renderDatePicker();
        _renderTimePicker();

        $(".readonly").on('keydown paste', function(e){
            e.preventDefault();
        });

        if (!reinitial) initEventListener();

        _setFormDefault();
    }

    var _setEarlyOTCheckbox = function() {
        var currentShift = $("#currentShift").val();
        var format = 'hh:mm'
        var currentTime = (_serverTime) ? moment(_serverTime) : moment();
        if (currentShift == shifts[2]) {
            if (currentTime.isAfter(moment('09:00', format))) {
                $("#earlyOvertime").prop("disabled", "disabled")
            }
        }

        if (currentShift == shifts[3]) {
            if (currentTime.isAfter(moment('11:00', format))) {
                $("#earlyOvertime").prop("disabled", "disabled")
            }
        }
    }

    var _setFormDefault = function() {
        if ($(datePickers[0]).val() == "") {
            var yesterday = (_serverTime) ? new Date(_serverTime) : new Date();
            yesterday.setDate(date.getDate() - 1);
            datepickerModule.setDate(datePickers[0], yesterday);
            $('input[name="Date"]').val(yesterday.getFullYear()+"/"+(yesterday.getMonth()+1)+"/"+yesterday.getDate());
        }

        if (isWeekend) {
            $("select[name='ShiftD']").val(8);
            $("input[name='Shift']").val(8);
        }

        if (isAdmin) $("#earlyOvertime").removeAttr("disabled");
    }

    var _renderDatePicker = function() {
        //!Available today and yesterday, need optimize later

        var availableDates = [];
        var today = (_serverTime) ? new Date(_serverTime) : new Date();
        availableDates.push(today.getFullYear() + "/" + (today.getMonth()+1) + "/" + today.getDate());

        var yesterday = (_serverTime) ? new Date(_serverTime) : new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        availableDates.push(yesterday.getFullYear() + "/" + (yesterday.getMonth()+1) + "/" + yesterday.getDate());
        if (isAdmin) {
            datepickerModule.initial(datePickers);
            return;
        }
        datepickerModule.initialWithOptions([
            {
                'selector': datePickers[0],
                'options': {
                    beforeShowDay: function(dt)
                    {
                        return available(dt, availableDates);
                    }
                }
            }
        ])
    }
    var available = function (date, availableDates) {
        dmy = date.getFullYear() + "/" + (date.getMonth()+1) + "/" + date.getDate();
        if ($.inArray(dmy, availableDates) != -1) {
            return true;
        } else {
            return false;
        }
    }


    var _generateTimeBlock = function() {
        var isEarlyOvertime = $("#earlyOvertime").is(':checked');
        var currentShift = $("#currentShift").val();

        if (isEarlyOvertime && (_shiftAllowedToOT.includes(parseInt(currentShift))) && !isWeekend && !isAdmin) {
            _renderTimeSelecter(currentShift);
        } else {
            _renderTimePicker()
        }
        _generateTotal();
    }

    var _renderTimePicker = function() {
        $('#fromTime').replaceWith('<input class="form-control " type="text" id="fromTime" name="FromTime" placeholder="from time" required>');
        $('#toTime').replaceWith('<input class="form-control " type="text" id="toTime" name="ToTime" placeholder="to time" required>');
        $('label[for="fromTime"]').show();
        $('label[for="toTime"]').show();
        timepickerModule.initial(timePickers);

        var currentShift = $("#currentShift").val();

        $(timePickers[0].selector).timepicker().on('show.timepicker', function(e) {
            if (!isWeekend && !isAdmin && !todayVNHoliday) {
                setTimeout(function(){
                    if (!shouldDisplayFromTimePicker) {
                        $(timePickers[0].selector).timepicker('hideWidget');
                        $(timePickers[0].selector).prop("readonly", "readonly")
                    }
                }, 0);
            }
        });

        if (!currentShift) return;
        shouldDisplayFromTimePicker = false;
        switch (currentShift) {
            case "1":
                timepickerModule.setTime(timePickers[0].selector, "16:00");
                timepickerModule.setTime(timePickers[1].selector, "16:00");
                break;

            case "2":
                timepickerModule.setTime(timePickers[0].selector, "19:00");
                timepickerModule.setTime(timePickers[1].selector, "19:00");
                break;

            case "3":
                timepickerModule.setTime(timePickers[0].selector, "21:00");
                timepickerModule.setTime(timePickers[1].selector, "21:00");
                break;
            default:
                shouldDisplayFromTimePicker = true;
                break;
        }
    }

    var _renderTimeSelecter = function(currentShift) {
        var fromOptions = toOptions = '';
        var format = 'hh:mm'
        var currentTime = (_serverTime) ? moment(_serverTime) : moment();
        if (currentShift == shifts[0]) {
            fromOptions = _generateSelectsOption([{value: '07:00',text: '07:00'}]);
            toOptions = _generateSelectsOption([{value: '08:00',text: '08:00'}]);
        }

        if (currentShift == shifts[2]) {
            var fromOptionSelectors = [];
            if (currentTime.isBefore(moment('08:00', format))) {
                fromOptionSelectors.push({value: '08:00',text: '08:00'});
            }

            if (currentTime.isBefore(moment('09:00', format))) {
                fromOptionSelectors.push({value: '09:00',text: '09:00'});
            }
            fromOptions = _generateSelectsOption(fromOptionSelectors);
            toOptions = _generateSelectsOption([{value: '10:00',text: '10:00'}]);
        }

        if (currentShift == shifts[3]) {
            var fromOptionSelectors = [];
            if (currentTime.isBefore(moment('10:00', format))) {
                fromOptionSelectors.push({value: '10:00',text: '10:00'});
            }

            if (currentTime.isBefore(moment('11:00', format))) {
                fromOptionSelectors.push({value: '11:00',text: '11:00'});
            }
            fromOptions = _generateSelectsOption(fromOptionSelectors);
            toOptions = _generateSelectsOption([{value: '12:00',text: '12:00'}]);
        }


        $('#fromTime').replaceWith('<select class="form-control w-135px float-left" id="fromTime" name="FromTime" placeholder="from time" required>'+fromOptions+'</select>');
        $('#toTime').replaceWith('<select class="form-control w-135px float-left" id="toTime" name="ToTime" placeholder="to time" required>'+toOptions+'</select>');
        $('label[for="fromTime"]').hide();
        $('label[for="toTime"]').hide();
    }

    var _generateSelectsOption = function (options) {
        var html = "";
        $.each(options, function(index, option) {
            html += "<option value='"+option.value+"'>"+option.text+"</option>"
        })
        return html;
    }

    var initEventListener = function() {
        $(document).on("change", "#fromTime, #toTime, #breaktime", function () { _generateTotal(); });
        $(document).on("change", "input[name='early-overtime']", function () { _generateTimeBlock();});
        $(document).on("change", "#date", function () { _getShiftlByDate(); });
        $(document).on("change", "#select-shift", function() { _setShiftByShiftSelector(); })
        $(document).on("click",  "#previewButton", function() { _previewForm(); })
        $(document).on("click",  "#submitFromPreview", function() { _submitForm(); })
    }

    var _submitForm = function() {
        $('#previewModal').modal('toggle');
        $('#ApForm').submit();
    }

    var _previewForm = function() {
        if (!formModule.validateForm(validateInputs)) {
            swal("Please out all fields", {
                buttons: {
                    cancel: "OK",
                },
            });
            return;
        }

        _renderPreviewModal();
    }

    var _renderPreviewModal = function() {
        var previewModalHTML = '';
        $.each(validateInputs, function(index, validateInput) {
            var element = validateInput['selector'];
            var value = $(element).val();
            if(element == "input[name='FromTime']" && $("#earlyOvertime").prop("checked")){
                value = $("select[name='FromTime']").val();
            }
            if(element == "input[name='ToTime']" && $("#earlyOvertime").prop("checked")){
                value = $("select[name='ToTime']").val();
            }
            if ($(element).attr("name") == "Shift"){
                value = ($('#is_holiday').val() == '') ? shifts[value] : shifts[value] + ' - ' + $('#is_holiday').val();
            }
            var label = validateInput['label'];
            previewModalHTML += "<tr>";
            previewModalHTML += "<td>"+label+"</td>";
            previewModalHTML += "<td>"+value+"</td>";
            previewModalHTML += "</tr>";
        })
        $('#previewModal').find("tbody").html(previewModalHTML)
        $('#previewModal').modal('toggle');
    }

    var _setShiftByShiftSelector = function() {
        var selectedVal = $("#select-shift").val();
        $("#Shift_save").val(selectedVal);
    }

    var _generateTotal = function() {
        var blockTotal = _generateTotalTimeBlock("#fromTime", "#toTime");
        if ($("#breaktime").is(":checked")) { blockTotal -= 60; }
        $('input[name="Total"]').val(blockTotal)
    }

    var _generateTotalTimeBlock = function(fromTime, toTime) {
        if ($(fromTime).val() !='' && $(toTime).val() !='') {
            var startTime = moment($(fromTime).val(), "HH:mm");
            var endTime = moment($(toTime).val(), "HH:mm");
            if (startTime > endTime) {
                endTime = moment(endTime).add(24, 'hours');
            }
            var duration =moment.duration(endTime.diff(startTime));
            if(duration.asMinutes().toFixed(0) < 0){
                return parseInt(duration.asMinutes().toFixed(0)) + 2400;
            }else{
                return parseInt(duration.asMinutes().toFixed(0));
            }
        }
        return 0;
    }
    var _getShiftlByDate = function() {
        fromDate = $('#date').val()
        toDate = $('#date').val();

        if (utilsModule().isWeekend(fromDate)) {
            $("#currentShift").val(8);
            $("#select-shift").val(8);
            $("#currentShift").val(8);
            $('#Shift_save').val(8);
            new initForm(reinitial = true);
            return;
        }

        jQuery.ajax({
            type: 'post',
            url: __baseUrl + '/mypage/getShiftlByDate',
            headers: {'X-CSRF-TOKEN': csrfToken},
            data:{'FromDate': fromDate, 'ToDate' : toDate}
        })
        .done(function (response) {
            console.log(response)

            if(response.is_holiday!=undefined){
                $('#is_holiday').val(response.is_holiday);
                todayVNHoliday = response.holiday_id;
            }else{
                $('#is_holiday').val('');
                todayVNHoliday = '';
            }

            if(response.shift[0] == undefined){
                swal("There is no shift in this day", {
                    buttons: {
                        cancel: "OK",
                    },
                });

            }else{
                $("#select-shift").val(response.shift[0]);
                $("#currentShift").val(response.shift[0]);
                $('#Shift_save').val(response.shift[0]);
                if(!_shiftAllowedToOT.includes(parseInt(response.shift[0]))){
                     $('#earlyOvertime').attr('disabled','disabled')
                    _renderTimePicker()
                }else{
                    $('#earlyOvertime').removeAttr('disabled')
                    _generateTimeBlock();
                }
                 _setEarlyOTCheckbox();
            }


        });
    }
    return {
        initForm:initForm
    }
})()

var alFormModule = (function() {
    var datePickers = ["#fromDate", "#toDate"]
    var listFromTime = $('#fromTime').html();
    var listToTime = $('#fromTo').html();
    var currentView = $("#view").val();
    var initForm = function() {
        datepickerModule.initial(datePickers,true);
        //$('#fromTime, #fromTo').prop('disabled', 'disabled')
        initDateFromTo();
        initEventListener();
        _getShiftlByDate();
        _generateTotalByDate();
    }

    var initEventListener = function() {
        $("#fromDate").change(function() {
            $('#toDate').datepicker( "setDate", $('#fromDate').val());
            $('#toDate').datepicker( "setStartDate", $('#fromDate').val());
        })

        $("#type-halfday").change(function() {
            _initFromDateToDateByShift();
         })
        $('#fromTime, #fromTo ').change(function() {
            _generateTotal();
        })
        $(".reason-type").change(function() {
            _setDateByReason(this.value)
         })
        $('#fromDate, #toDate').change(function() {
            _generateTotalByDate();
            _getShiftlByDate();
        })
        $("#previewButton").click(function(event) {
            console.log(_checkRequired());
            if(_checkRequired() == false){
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
        $('#submitFromPreview').click(function(event) {
            $('#previewModal').modal('toggle');
            $('#submit-form').click();
        });
        //if (currentView == "ulform") {
        //     $('.reason-type').change(function() {
        //        if($(this).val() == 1){
        //             $('#fromDate').replaceWith('<input class="form-control w-135px readonly " onchange="changeFrom()" type="text" id="fromDate" name="FromDate" placeholder="from date" required>');
        //             $('#toDate').replaceWith('<input class="form-control w-135px readonly " type="text" id="toDate" name="ToDate" placeholder="to date" required>');
        //            datepickerModule.initial(datePickers,false);
        //            initDateFromTo();
        //        }else{
        //             $('#fromDate').replaceWith('<input class="form-control w-135px readonly " onchange="changeFrom()" type="text" id="fromDate" name="FromDate" placeholder="from date" required>');
        //             $('#toDate').replaceWith('<input class="form-control w-135px readonly " type="text" id="toDate" name="ToDate" placeholder="to date" required>');
        //
        //            datepickerModule.initial(datePickers,true);
        //            initDateFromTo();
        //        }
        //    })
        //}
    }

    var _setDateByReason = function(value) {
        if(value == 4){
            initDateFromTo();
            var tdate = (_serverTime) ? new Date(_serverTime) : new Date();;
            $('#fromDate').datepicker('setStartDate',false);
            $('#toDate').datepicker('setStartDate',false);
            var ddate = _getDateToLeave();
            _setStartDate(ddate);
            $('#reason_other').attr('required','')
        }else{
            $('#fromDate').datepicker('setStartDate',false);
            $('#toDate').datepicker('setStartDate',false);
            var ddate = (_serverTime) ? new Date(_serverTime) : new Date();;
            _setStartDate(ddate);

            $('#fromDate').datepicker( "setDate", ddate);
            $('#toDate').datepicker( "setDate", ddate);

            $('#reason_other').removeAttr('required')
        }
        _generateTotalByDate()
    }

    var initDateFromTo = function() {
        var tdate = (_serverTime) ? new Date(_serverTime) : new Date();;
        _setStartDate(tdate);
        var ddate = _getDateToLeave();
        $('#fromDate').datepicker( "setDate", ddate);
        $('#toDate').datepicker( "setDate", ddate);

    }
    var _setStartDate = function(date){
        console.log($('#isAdmin').val())
        if($('#isAdmin').val() == "true"){
            $('#fromDate').datepicker('setStartDate',false);
            $('#toDate').datepicker('setStartDate',false);
        }else{
            $('#fromDate').datepicker('setStartDate',date);
            $('#toDate').datepicker('setStartDate',date);
        }

    }
    var _initFromDateToDateByShift = function() {
        if($("#type-halfday").prop('checked') == true ) {
            _getShiftlByDate()
        }else{
            _getShiftlByDate();
        }
        _generateTotalByDate();
    }

    var _generateTotal = function() {
        if(document.getElementById('fromTime').value !=''){
            document.getElementById('fromTo').value = moment(document.getElementById('fromTime').value, "HH:mm").add(4,'hours').format('HH:mm');
        }else{
            $('#total-day').val(0)
            var startTime = moment($('#fromTime').val(), "HH:mm");
            var endTime = moment($('#fromTo').val(), "HH:mm");
            var duration = moment.duration(endTime.diff(startTime));
        }
        _generateTotalByDate();
    }

    var _generateTotalByDate = function() {
        total_day = workingDaysBetweenDates(new Date($('#fromDate').val()),new Date($('#toDate').val() ))
        if($("#type-halfday").prop('checked') == true ) {
            $('#total-day').val(total_day*0.5)
         }else $('#total-day').val(total_day)
    }

    var  workingDaysBetweenDates = function(startDate, endDate) {
        if (endDate < startDate) return 0;
        var millisecondsPerDay = 86400 * 1000;
        startDate.setHours(0,0,0,1);
        endDate.setHours(23,59,59,999);
        var diff = endDate - startDate;
        var days = Math.ceil(diff / millisecondsPerDay);
        var weeks = Math.floor(days / 7);
        days = days - (weeks * 2);
        var startDay = startDate.getDay();
        var endDay = endDate.getDay();
        if (startDay - endDay > 1) days = days - 2;
        if (startDay == 0 && endDay != 6) days = days - 1
        if (endDay == 6 && startDay != 0) days = days - 1
        return days;
    }
    var _getShiftlByDate = function() {
        fromDate = $('#fromDate').val()
        toDate = $('#toDate').val();
        jQuery.ajax({
            type: 'post',
            url: __baseUrl + '/mypage/getShiftlByDate',
            headers: {'X-CSRF-TOKEN': csrfToken},
            data:{'FromDate': fromDate, 'ToDate' : toDate}
        })
        .done(function (response) {
            console.log(response);
            if(response.length >= 2) {
                $('#toDate').datepicker( "setDate", $('#fromDate').val());
                utilsModule().hideLoadingModal();
                setTimeout(function(){ swal("There are more than one shift, please try again", {
                    buttons: {
                        cancel: "OK",
                    },
                }); }, 500);

                return
            }else{
                if($("#type-halfday").prop('checked') !== true ) {
                    if(response.shift[0] == 1){
                    fromOptions = _generateSelectsOption([{value: '07:00',text: '07:00'}]);
                    toOptions = _generateSelectsOption([{value: '16:00',text: '16:00'}]);

                    }else if(response.shift[0] == 2){
                        fromOptions = _generateSelectsOption([{value: '10:00',text: '10:00'}]);
                        toOptions = _generateSelectsOption([{value: '19:00',text: '19:00'}]);

                    }else if(response.shift[0] == 3){
                        fromOptions = _generateSelectsOption([{value: '12:00',text: '12:00'}]);
                        toOptions = _generateSelectsOption([{value: '21:00',text: '21:00'}]);

                    }else if($('#staffID').val() == "H0007"){
                        fromOptions = _generateSelectsOption([{value: '08:00',text: '08:00'}]);
                        toOptions = _generateSelectsOption([{value: '17:00',text: '17:00'}]);

                    }else{
                        fromOptions = _generateSelectsOption([{value: '07:30',text: '07:30'}]);
                        toOptions = _generateSelectsOption([{value: '16:30',text: '16:30'}]);
                    }
                }else{
                    if(response.shift[0] == 1){
                    fromOptions = _generateSelectsOption([{value: '07:00',text: '07:00'},{value: '12:00',text: '12:00'}]);
                    toOptions = _generateSelectsOption([{value: '11:00',text: '11:00'},{value: '16:00',text: '16:00'}]);

                    }else if(response.shift[0] == 2){
                        fromOptions = _generateSelectsOption([{value: '10:00',text: '10:00'},{value: '15:00',text: '15:00'}]);
                        toOptions = _generateSelectsOption([{value: '14:00',text: '14:00'},{value: '19:00',text: '19:00'}]);

                    }else if(response.shift[0] == 3){
                        fromOptions = _generateSelectsOption([{value: '12:00',text: '12:00'},{value: '17:00',text: '17:00'}]);
                        toOptions = _generateSelectsOption([{value: '16:00',text: '16:00'},{value: '21:00',text: '21:00'}]);

                    }else if($('#staffID').val() == "H0007"){
                        fromOptions = _generateSelectsOption([{value: '08:00',text: '08:00'},{value: '13:00',text: '13:00'}]);
                        toOptions = _generateSelectsOption([{value: '12:00',text: '12:00'},{value: '17:00',text: '17:00'}]);

                    }else{
                        fromOptions = _generateSelectsOption([{value: '07:30',text: '07:30'},{value: '12:30',text: '12:30'}]);
                        toOptions = _generateSelectsOption([{value: '11:30',text: '11:30'},{value: '16:30',text: '16:30'}]);

                    }
                }

                $('#fromTime').html(fromOptions);
                $('#fromTo').html(toOptions);

            }


        });
    }
    var _generateSelectsOption = function (options) {
        var html = "";
        $.each(options, function(index, option) {
            html += "<option value='"+option.value+"'"+option.selected+" >"+option.text+"</option>"
        })
        return html;
    }
    var _generatePreview = function(){

        basicInfo = _generateDataToPreview();
        $.each(basicInfo, function( classSelector, value ) {
            var targetElement = $("#previewModal").find("."+value.name+'-preview');
            targetElement.html(value.value)
        });
    }
    var _generateDataToPreview = function() {

        basicInfo =  $('#ApForm').serializeArray();
        dataInfor = [];
        $.each(basicInfo, function( classSelector, value ) {
            if(value.name == 'Type'){
                text = $('#typelable-'+value.value).text()

            }else if(value.name == 'Reason'){
                if (currentView == "plform") {
                    text = $('#reasontypelable-'+value.value).text()
                    if(value.value == 4){
                        text = $('#reason_other').val()
                    }
                }else{
                    text = $('#reasontypelable-'+value.value).text()
                    if(value.value == 2){
                        text = $('#reason_other').val()
                    }
                }
            }else{
                text = value.value
            }
            dataInfor.push({name: value.name , value :text })
        })
        return dataInfor;
    }
    var _getDateToLeave = function() {
        for (var i = 3; i <= 5; i++) {
            var dateObj = (_serverTime) ? new Date(_serverTime) : new Date();
            ddate = new Date(dateObj.setDate(dateObj.getDate() + i));
            if(!(ddate.getDay() == 0 || ddate.getDay() == 6)){
                return ddate
             }
        }
    }
    var _checkRequired = function(){
        if (currentView == "plform") {
            if($("input[name='Type']:checked").val() ==undefined || $("input[name='Reason']:checked").val() == undefined){
                return false
            }else{
                if($("input[name='Reason']:checked").val() != 5){
                    if($('#reason_other').val() ==''){
                        return false;
                    }
                }
                return true
            }
        }else{
            //vi add check ul form with 1 reason
            // if($('#reason_other').val() ==''){
            //     return false;
            // }
             if($("input[name='Type']:checked").val() ==undefined || $("input[name='Reason']:checked").val() == undefined){
                return false
            }else{
                if($("input[name='Reason']:checked").val() == 2){
                    if($('#reason_other').val() ==''){
                        return false;
                    }
                }
                return true
            }
        }
    }
    return {
        initForm:initForm
    }

})()
var scoreFormModule = (function() {
    var myVar = '';
    var initForm = function() {
        //getTime();
        endTime = $('#time_left').val();
        $('.container').show();
        makeTimer(endTime)
    }


    var makeTimer = function(timeShow) {
        clearInterval(myVar);
        time = timeShow;
        var c_second = 1;
        var c_minute = c_second * 60;
        var c_hour = c_minute * 60;
        var c_day = c_hour * 24;
        var c_week = c_day * 7;
       // countDown = new Date(time).getTime()
        now = 0;
        myVar = setInterval(function() {
        now  = now + c_second;
        distance = timeShow - now;

        if(distance > 0){
            document.getElementById('days').innerText = Math.floor((distance % (c_week))/(c_day));
            document.getElementById('hours').innerText = Math.floor((distance % (c_day)) / (c_hour));
            document.getElementById('minutes').innerText = Math.floor((distance % (c_hour)) / (c_minute));
            document.getElementById('seconds').innerText = Math.floor((distance % (c_minute)) / c_second);
        } else{
            $('.container').hide();
            $('.showScore').show();
        }

        if(c_hour == 0 && c_minute == 0 && c_second == 0){
            $('.container').hide();
            $('.showScore').show();
        }
       }, 1000)
    }

    return {
        initForm:initForm
    }

})()
var paidleaveModule = (function() {
    var initForm = function() {

        initEventListener();
        _initCk();
        $('.edit-content').hide();
        $('.show-content').show();
    }
    var initEventListener = function() {
        $("#cancelRules").click(function(){

            location.reload();
        });
        $("#editCompanyBasicRules").click(function(){
            $('.edit-content').show();
            $('.show-content').remove();
        });
        $("#saveCompanyBasicRules").click(function(){

            $.ajax({
                type: 'post',
                url: baseUrl + 'mypage/saveRegulation',
                headers: {'X-CSRF-TOKEN': csrfToken},
                data: $("#ApForm").serialize(),
            })
            .done(function (response) {
                if (response.Success) {
                   // location.reload();
                }else{
                    swal(response.Message, {
                        buttons: {
                            cancel: "OK",
                        },
                    });
                }
            });
        });
    }
    var _initCk = function() {
        CKEDITOR.replace( 'Contents', {
            extraPlugins: 'imageuploader',
            "language" : "jp"
        });

    }
    return {
        initForm:initForm
    }
})()
var unpaidleaveModule = (function() {
    var initForm = function() {

        initEventListener();
        _initCk();
        $('.edit-content').hide();
        $('.show-content').show();
    }
    var initEventListener = function() {
        $("#cancelRules").click(function(){

            location.reload();
        });
        $("#editCompanyBasicRules").click(function(){
            $('.edit-content').show();
            $('.show-content').remove();
        });
        $("#saveCompanyBasicRules").click(function(){

            $.ajax({
                type: 'post',
                url: baseUrl + 'mypage/saveRegulation',
                headers: {'X-CSRF-TOKEN': csrfToken},
                data: $("#ApForm").serialize(),
            })
            .done(function (response) {
                if (response.Success) {
                   // location.reload();
                }else{
                    swal(response.Message, {
                        buttons: {
                            cancel: "OK",
                        },
                    });
                }
            });
        });
    }
    var _initCk = function() {
        CKEDITOR.replace( 'Contents', {
            extraPlugins: 'imageuploader',
            "language" : "jp"
        });

    }
    return {
        initForm:initForm
    }
})()
var companybasicrulesModule = (function() {
    var initForm = function() {

        initEventListener();
        _initCk();
        $('.edit-content').hide();
        $('.show-content').show();
    }
    var initEventListener = function() {
        $("#cancelRules").click(function(){

            location.reload();
        });
        $("#editCompanyBasicRules").click(function(){
            $('.edit-content').show();
            $('.show-content').remove();
        });
        $("#saveCompanyBasicRules").click(function(){

            $.ajax({
                type: 'post',
                url: baseUrl + 'mypage/saveRegulation',
                headers: {'X-CSRF-TOKEN': csrfToken},
                data: $("#ApForm").serialize(),
            })
            .done(function (response) {
                if (response.Success) {
                   // location.reload();
                }else{
                    swal(response.Message, {
                        buttons: {
                            cancel: "OK",
                        },
                    });
                }
            });
        });
    }
    var _initCk = function() {
        CKEDITOR.replace( 'Contents', {
            extraPlugins: 'imageuploader',
            "language" : "jp"
        });

    }
    return {
        initForm:initForm
    }
})()
//$(document).ready(function() {


//     $("#editCompanyBasicRules").click(function(){
//         $("#cancelRules").show();
//         $("#companyBasicRulesText").html($(".rulesContent").html());
//         tinymce.init({
//             selector: '#companyBasicRulesText',
//             height: 500,
//             plugins: [
//               'advlist autolink lists link image charmap print preview hr anchor pagebreak',
//               'searchreplace wordcount visualblocks visualchars code fullscreen',
//               'insertdatetime media nonbreaking save table directionality',
//               'emoticons template paste textpattern imagetools'
//             ],
//             toolbar1: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
//             toolbar2: 'print preview media | forecolor backcolor emoticons',
//             entity_encoding: 'raw',
//             force_br_newlines : false,
//             force_p_newlines : false,
//             forced_root_block : false,
//             convert_newlines_to_brs : false,
//             remove_linebreaks : false,
//             remove_trailing_brs: false,
//             valid_elements: "@[class],p[style],h3,h4,h5,h6,a[href|target],strong/b,div[align],i,br,table,tbody,thead,tr,td,ul,ol,li,img[src]",
//         });
//         $("#saveCompanyBasicRules").show();
//         $("#editCompanyBasicRules").hide();
//     });
//     $("#saveCompanyBasicRules").click(function(){
//         var regContent = tinyMCE.activeEditor.getContent();
//         var regName = $('#regName').val();
//         $.ajax({
//             type: 'post',
//             url: baseUrl + 'mypage/saveRegulation',
//             data: {name:regName,content:regContent.replace("<br>","")},
//             headers: {'X-CSRF-TOKEN': csrfToken}
//         })
//         .done(function (response) {
//             $(".rulesContent").html(regContent);
//             if (response.Success) {
//                 swal({
//                     title: "Success!",
//                     text: response.Message,
//                     type: "success",
//                     confirmButtonText: "OK"
//                   }).then(function(){
//                     location.reload();
//                   });
//             }else{
//                 swal(response.Message, {
//                     buttons: {
//                         cancel: "OK",
//                     },
//                 });
//             }
//         });
//     });
//     $("#cancelRules").click(function(){
//         if( tinymce.editors.length > 0 )
//         {
//             for( i = 0; i < tinymce.editors.length; i++ )
//             {
//                 tinyMCE.editors[ i ].remove();
//             }
//         }
//         $("#companyBasicRulesText").fadeOut();
//         $("#cancelRules").hide();
//         $("#saveCompanyBasicRules").hide();
//         $("#editCompanyBasicRules").show();
//     });
// });
function  changeFrom(){
    $('#toDate').datepicker( "setDate", $('#fromDate').val());
    $('#toDate').datepicker( "setStartDate", $('#fromDate').val());
}

function requestExportEmerg() {
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'mypage/emerg',
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
