$( document ).ready(function() {
    var currentView = $("#view").val();   
    var monthPickerId = '#monthPicker';
    jQuery(monthPickerId).monthpicker({
        monthNames:["/01", "/02", "/03", "/04", "/05", "/06", "/07", "/08", "/09", "/10", "/11", "/12"],
        showOn:     "both",
        buttonImage: baseUrl+"img/calendar1.png",
        buttonImageOnly: true,
        showButtonPanel: false,
        changeYear: false,
        yearRange: 'c-2:c+2',
        dateFormat: 'yyMM',
        onSelect: function(dateText, inst) {
            window.location.href = window.location.pathname + "?month=" + dateText;
        }
    });

    if (currentView == "stworking") {
        stFormModule.initForm();
        if(typeof isUpdated  !== "undefined" && isUpdated){
            swal('Data was updated!');
        }
    }
});
var stFormModule = (function() {    
    var iShunin = $('#iShunin').val() === "true" ? true : false
    var isAdmin = $('#isAdmin').val() === "true" ? true : false
    var initForm = function() {
        if (iShunin) {
            initEventListener();
        }
        if(typeof listData !== undefined){
            listData.forEach(function(element) {
                var staffId = element.StaffID;
                var d =  new Date(element.SaturdayDate);

                var year ='' +  d.getFullYear();
                var month = d.getMonth() + 1;
                month = month + '';
                if(month.length < 2){
                    month = '0' + month;
                }
                var day = d.getDate();
                day = day + '';
                if(day.length < 2){
                    day = '0' + day;
                }
                date = year + month + day;
                checkbox = $('#checked_'+staffId+'_'+date);
                checkbox.prop("checked",true);
                $('#icon_'+staffId+'_'+date).show();
            });
            countInit();
        }
    }
    var initEventListener = function() {
        $('.circle_click').click(function(event) {
            staff_id = $(this).attr('staff_id');
            date = $(this).attr('date');
            key = $(this).attr('key');
            checkbox = $('#checked_'+staff_id+'_'+date);
            var d = new Date();
            var year ='' +  d.getFullYear();
            var month = d.getMonth() + 1;
            month = month + '';
            if(month.length < 2){
                month = '0' + month;
            }
            var day = d.getDate();
            day = day + '';
            if(day.length < 2){
                day = '0' + day;
            }
            var now = year + month + day;
            if(parseInt(now) > parseInt(date) && !isAdmin){
                return;
            }
        	checkbox.prop("checked", !checkbox.prop("checked"));
            if(checkbox.prop("checked") === true){
            	$('#icon_'+staff_id+'_'+date).show();
            }else{
            	$('#icon_'+staff_id+'_'+date).hide();
            }
            countInit();
        });
    }
    var _countSubTotal = function(keyClass){
    	$('#'+keyClass).html($('.'+keyClass+':checkbox:checked').length)
    }
    var _countTotal = function(date){
        total1 = ($('#1_'+date).html() == '')?0:parseInt($('#1_'+date).html());
        total2 = ($('#2_'+date).html() == '')?0:parseInt($('#2_'+date).html());
        total3 = ($('#3_'+date).html() == '')?0:parseInt($('#3_'+date).html());
        total4 = ($('#4_'+date).html() == '')?0:parseInt($('#4_'+date).html());
        total5 = ($('#5_'+date).html() == '')?0:parseInt($('#5_'+date).html());
        total6 = ($('#6_'+date).html() == '')?0:parseInt($('#6_'+date).html());
        $('#total_by_'+date).html(total1 + total2 + total3 + total4 + total5 + total6);
    }
    var countInit = function() {
        var allTable = $("table");
        var subtotal = {};
        var totalS = {};
        var totalM = {};
        $(".total").html("0");
        $.each(allTable,function (i,element) {
            var dataSat = $(element).find("tbody tr td.circle_click");
            $.each(dataSat,function(k,child) {
                if($(child).find("input[type=checkbox]").prop('checked')){
                    var key = $(child).attr('key') + '_' + $(child).attr('date');
                    if(subtotal[key] == undefined){
                        subtotal[key] = 0;
                    }
                    if(totalS['total_' + $(child).attr('date')] == undefined){
                        totalS['total_' + $(child).attr('date')] = 0;
                    }
                    if(totalM['total_' + $(child).attr('date')] == undefined){
                        totalM['total_' + $(child).attr('date')] = 0;
                    }
                    subtotal[key] += 1;

                    if(parseInt($(child).attr('key')) <= 3){
                        totalS['total_' + $(child).attr('date')] += 1;
                    } else{
                        totalM['total_' + $(child).attr('date')] += 1;
                    }
                }
            })
        })
        // Write data
        $.each(totalS,function(key,value) {
            var k = key.replace('total_','');
            $('#total_3_'+k).html(value);
        })
        $.each(totalM,function(key,value) {
            var k = key.replace('total_','');
            $('#total_6_'+k).html(value);
        })
        $.each(subtotal,function(key,value) {
            $('#' + key).html(value);
        })
    }
    return {
        initForm:initForm
    }
})()