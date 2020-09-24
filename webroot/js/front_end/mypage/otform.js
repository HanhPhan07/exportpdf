// $(function () {  

//     // Time Picker
//     jQuery('#timepicker').timepicker({
//         minuteStep: 15,
//         showMeridian: false,
//         change: function(time) {
//          console.log(time)
//         }
//     });
//     jQuery('#timepicker3').timepicker({
//         minuteStep: 15,
//         showMeridian: false,
//         change: function(time) {
//             console.log(time)
//         }
//     });
//     $("#datepicker1").datepicker({         
//         autoclose: true,         
//         todayHighlight: true 
//     }).datepicker(' ', new Date());

//     $('#timepicker').on('change', function() {  
//         tmTotalHrsOnSite() 
//         //console.log(moment($("#timepicker").val(), "HH:mm"));
//     });
//     $('#timepicker3').on('change', function() {  
//         tmTotalHrsOnSite()   
//         //console.log(moment($("#timepicker").val(), "HH:mm"));
//     });
// });

// function tmTotalHrsOnSite () {
//        if ($("#timepicker").val() !='' && $("#timepicker3").val() !='') {

//           var startTime = moment($("#timepicker").val(), "HH:mm");
//           var endTime = moment($("#timepicker3").val(), "HH:mm");
//           var duration =moment.duration(endTime.diff(startTime));
//           console.log(duration.asMinutes().toFixed(0))
//           if(duration.asMinutes().toFixed(0) < 0){
//             $('input[name="Total"]').val(parseInt(duration.asMinutes().toFixed(0)) + 2400)
//           }else{
//             $('input[name="Total"]').val(duration.asMinutes().toFixed(0))
//           }
//           // 
//       }

// }
