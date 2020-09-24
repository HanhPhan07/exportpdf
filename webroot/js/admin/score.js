var time = '';
var myVar = '';
var timePickers = [

        {
            selector: "#toTime",
            options: {
                showMeridian: false,
                minuteStep: 1,
                secondStep: 5,
                showSeconds: true,
            }
        }
    ];
jQuery(document).ready(function() {
    $('.saveTime').on('click', function () {
        if($('#toTime').val()){
            saveTime()
        }
        return
    });
     $('.showScore').on('click', function () {
        showScore(time)
    });
    endTime = $('#time_left').val();
    makeTimer(endTime)
    timepickerModule.initial(timePickers);

    $('.stopTime').on('click', function(e){
        e.preventDefault()
        stopTime()
    })
});

function saveTime()
{
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'admin/score/saveTime',
        headers: {'X-CSRF-TOKEN': csrfToken},
        data: $("#ApForm").serialize(),
    })
    .done(function (response) {
        utilsModule().hideLoadingModal();
        console.log(response)
        if(response !== 'false'){
            $('#timeSet').attr('readonly','readonly')
            $('.container').show();
            $('.stopTime').show();
            $('.showScore').hide();
           // document.getElementById('days').innerText = 0;
            document.getElementById('hours').innerText = 0;
            document.getElementById('minutes').innerText = 0;
            document.getElementById('seconds').innerText = 0;
            makeTimer(response)
            //myStr = JSON.parse(response);
           // makeTimer2(myStr.replace(/-/g, '/'))
        }
    });
    utilsModule().hideLoadingModal();
}

function stopTime()
{
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'get',
        url: baseUrl + 'admin/score/stopTime',
        // headers: {'X-CSRF-TOKEN': csrfToken},
        // data: $("#ApForm").serialize(),
    })
    .done(function (response) {
        if(response == "success"){
            utilsModule().hideLoadingModal();
            makeTimer(0)
            timepickerModule.initial(timePickers);
        } else {
            alert('Have error. Please reload page and try again')
        }

    });
    utilsModule().hideLoadingModal();
}

function showScore(timeShow){
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'admin/score/getScore',
        headers: {'X-CSRF-TOKEN': csrfToken},
        data: {'time':timeShow },
    })
    .done(function (response) {
        utilsModule().hideLoadingModal();
        data = JSON.parse(response);
        html = '';
        $.each(data, function(index,value) {
            console.log(value)
            html += '<tr> <th scope="row"  class="text-center">'+value.Team+'</th> <td class="auto-style1 text-center"> '+ value.SUM+'</td> </tr> '

        })
        $('#listTeam').html(html)
    });
     utilsModule().hideLoadingModal();
}

/*
 * Reset Password
 */
var timepickerModule = (function() {
    var timepickerObjects = []

    var _setTimePickerIdsMethod = function(objects) {
        if (!jQuery.isArray(objects)) return _responseError("setTimePickerIdsMethod input params must be an array");
        timepickerObjects = objects
    }

    var initialMethod = function(objects) {
        if (objects.length == 0) return _responseError("Missing Input params: list of datepicker objects");
        _setTimePickerIdsMethod(objects)
        jQuery.each(timepickerObjects, function( index, timepickerObject ) {
            jQuery(timepickerObject['selector']).timepicker(timepickerObject['options']);
        });
    }


    return {
        initial: initialMethod,
    }
})()
 var makeTimer = function(timeShow) {

        clearInterval(myVar);
        var time = timeShow;
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
          //  document.getElementById('days').innerText = Math.floor((distance % (c_week))/(c_day));
            document.getElementById('hours').innerText = Math.floor((distance % (c_day)) / (c_hour));
            document.getElementById('minutes').innerText = Math.floor((distance % (c_hour)) / (c_minute));
            document.getElementById('seconds').innerText = Math.floor((distance % (c_minute)) / c_second);
        } else{
            $('.container').hide();
            $('.showScore').show();
            $('.stopTime').hide();
        }

        if(c_hour == 0 && c_minute == 0 && c_second == 0){
            $('.container').hide();
            $('.showScore').show();
            $('.stopTime').hide();
        }
       }, 1000)
    }
