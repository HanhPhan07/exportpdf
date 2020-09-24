$( document ).ready(function() {
    window.onload = date_time('date_time');

    var currentView = $("#view").val();
    if (currentView == "score") {
        scoreFormModule.initForm()
    }
    
});
var scoreFormModule = (function() {
    var myVar = '';
    var initForm = function() {
        //getTime();
        endTime = $('#time_left').val();
        $('.container').show();
        makeTimer(endTime)
        $('.showScore').on('click', function () {        
        showScore()
    });
    }
    var  showScore = function(timeShow){
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'score/getScore',
        headers: {'X-CSRF-TOKEN': csrfToken},
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

        if(distance >= 0){
            var hours = Math.floor((distance % (c_day)) / (c_hour));
            var minutes = Math.floor((distance % (c_hour)) / (c_minute));
            var seconds = Math.floor((distance % (c_minute)) / c_second);

          //  document.getElementById('days').innerText = Math.floor((distance % (c_week))/(c_day));
            document.getElementById('hours').innerText = hours < 10 ? '0' + '' + hours : hours;
            document.getElementById('minutes').innerText = minutes < 10 ? '0' + '' + minutes : minutes;
            document.getElementById('seconds').innerText = seconds < 10 ? '0' + '' + seconds : seconds;
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