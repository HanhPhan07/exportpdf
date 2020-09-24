var datepickerModule2 = (function() {
    var datepickerIds = []

    var _setDatePickerIdsMethod = function(ids) {
        if (!jQuery.isArray(ids)) return _responseError("setDatePickerIdsMethod input params must be an array");
        datepickerIds = ids
    }

    var initialMethod = function(ids) {
        if (ids.length == 0) return _responseError("Missing Input params: list of datepicker ids");
        _setDatePickerIdsMethod(ids)
        jQuery.each(datepickerIds, function( index, datepickerId ) {
            jQuery(datepickerId).datepicker();
            jQuery(datepickerId).datepicker("option", "showOn", 'both');
            jQuery(datepickerId).datepicker("option", "buttonImageOnly", true);
            jQuery(datepickerId).datepicker("option", "buttonImage", baseUrl+'/img/calendar.png');
            jQuery(datepickerId).datepicker("option", "dateFormat", "yy/mm/dd");
            jQuery('body').on('propertychange input', datepickerId, function() {
                jQuery(datepickerId).val(jQuery(datepickerId).val().replace(/[^\d\/]+/g,''))
            });
        });
    }

    var initialDateRange = function(dateFrom, dateTo){
        var minDate ;
        var maxDate;
        var mDate
        var j = jQuery.noConflict();
        j( dateFrom ).datepicker({
            onSelect: function() {
                setMinMaxDate(j(dateFrom),j(dateTo));
            }
        });

        j( dateTo ).datepicker({
            onSelect: function() {
                setMinMaxDate(j(dateFrom),j(dateTo));
            }
        });

        var tdate = new Date();
        var ddate = new Date(tdate.setDate(tdate.getDate() - 30));
        j( dateFrom ).datepicker("setDate", ddate);
        j( dateTo ).datepicker();
        j( dateTo ).datepicker("setDate",new Date());

        setOptions(j(dateFrom));
        setOptions(j(dateTo));

    }

    var setMinMaxDate = function(dateFrom, dateTo){
        minDate = dateFrom.datepicker("getDate");
        maxDate = dateTo.datepicker("getDate");
        dateTo.datepicker( "option", "minDate", minDate);
        dateTo.datepicker( "option", "maxDate", maxDate);
    }

    var setOptions = function(element){
        element.datepicker("option", "showOn", 'both');
        element.datepicker("option", "buttonImageOnly", true);
        element.datepicker("option", "buttonImage", baseUrl+'/img/calendar.png');
        element.datepicker("option", "dateFormat", "yy/mm/dd");
    }

    var setDate = function(id, date) {
        jQuery(id).datepicker("setDate", date);
    }

    var getDate = function(id) {
        return jQuery(id).datepicker("getDate");
    }

    var _responseError = function(errMessage) {
        alert(errMessage)
    }

    return {
        initial: initialMethod,
        setDate: setDate,
        getDate: getDate,
        dateRange: initialDateRange
    }
})();

var monthPickerModule = (function() {
    var monthPickerIds = []

    var _setMonthPickerIdsMethod = function(ids) {
        if (!jQuery.isArray(ids)) return _responseError("setMonthPickerIdsMethod input params must be an array");
        monthPickerIds = ids
    }

    var initialMethod = function(ids) {
        if (ids.length == 0) return _responseError("Missing Input params: list of datepicker ids");
        _setMonthPickerIdsMethod(ids)
        jQuery.each(monthPickerIds, function( index, datepickerId ) {
            jQuery(datepickerId).monthpicker({
                monthNames:["/01", "/02", "/03", "/04", "/05", "/06", "/07", "/08", "/09", "/10", "/11", "/12"],
                //monthNamesShort: ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"],
                showOn:     "both",
                buttonImage: baseUrl+"img/calendar1.png",
                buttonImageOnly: true,
                showButtonPanel: false,
                changeYear: false,
                yearRange: 'c-2:c+2',
                dateFormat: 'yyMM'
            });
            jQuery('body').on('propertychange input', datepickerId, function() {
                jQuery(datepickerId).val(jQuery(datepickerId).val().replace(/[^\d\/]+/g,''))
            });
        });

    }

    var initialOrdinaryMethod = function(ids) {
        if (ids.length == 0) return _responseError("Missing Input params: list of datepicker ids");
        _setMonthPickerIdsMethod(ids)
        jQuery.each(monthPickerIds, function( index, datepickerId ) {
            jQuery(datepickerId).monthpicker({
                monthNames: ["/01", "/02", "/03", "/04", "/05", "/06", "/07", "/08", "/09", "/10", "/11", "/12"],
                //monthNamesShort: ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"],
                showOn:     "both",
                buttonImage: baseUrl+"img/calendar1.png",
                buttonImageOnly: true,
                showButtonPanel: false,
                changeYear: false,
                yearRange: 'c-2:c+2',
                dateFormat: 'yyMM'
            });
            jQuery('body').on('propertychange input', datepickerId, function() {
                jQuery(datepickerId).val(jQuery(datepickerId).val().replace(/[^\d\/]+/g,''))
            });
        });

    }

    var setDate = function(id, date) {
        jQuery(id).val(date);
    }

    var _responseError = function(errMessage) {
        alert(errMessage)
    }

    return {
        initial: initialMethod,
        setDate: setDate,
        initialOrdinary: initialOrdinaryMethod
    }
})();

var formModule = (function() {
    var validateForm = function(validateInputs = []) {
        $flag = true;
        $.each(validateInputs, function(index, validateInputs) {
            switch (validateInputs['type']) {
                case 'required':
                    if ($(validateInputs['selector']).val() == "") {
                        $flag = false;
                    }
                    break;

                default:
                    break;
            }
        })
        return $flag;
    }

    return {
        validateForm: validateForm
    }
})();

var utilsModule = (function() {
    var displayLoadingModal = function() {
        swal({
            text: 'Please wait a moment...',
            icon: baseUrl + "/img/ajax-loader-tr.gif",
            closeOnClickOutside: false,
            closeOnEsc: false,
            buttons: false
        });
    }

    var hideLoadingModal = function() {
        swal.close();
    }

    var isMobile = function() {
        return (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent)
        || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0,4)))
    }

    var _adjustFixedHeaderWidth = function() {
        var parentwidth = $("#content").width();
        $("#topnav").width(parentwidth);
    }

    var initStickyHeader = function() {
        $("#content").sizeChanged(function(){
            _adjustFixedHeaderWidth()
        })
    }

    var isWeekend = function(dateInp) {
        var date = new Date(dateInp);
        var day = date.getDay();
        return (day === 6) || (day === 0);
    }

    return {
        displayLoadingModal: displayLoadingModal,
        hideLoadingModal: hideLoadingModal,
        isMobile: isMobile,
        initStickyHeader: initStickyHeader,
        isWeekend: isWeekend
    }
});

var datepickerModule = (function() {
    var datepickerIds = []

    var _setDatePickerIdsMethod = function(ids) {
        if (!jQuery.isArray(ids)) return _responseError("setDatePickerIdsMethod input params must be an array");
        datepickerIds = ids
    }

    var initialMethod = function(ids,plul=false) {
        if (ids.length == 0) return _responseError("Missing Input params: list of datepicker ids");
        _setDatePickerIdsMethod(ids)
        jQuery.each(datepickerIds, function( index, datepickerId ) {
            jQuery(datepickerId).datepicker({
                autoclose: true,
                todayHighlight: true,
                orientation: "bottom left",
                disableTouchKeyboard: true,
                daysOfWeekDisabled: (plul)?"0,6":''

            });
            jQuery('body').on('propertychange input', datepickerId, function() {
                jQuery(datepickerId).val(jQuery(datepickerId).val().replace(/[^\d\/]+/g,''))
            });
        });
    }

    var initialWithOptionsMethod = function(objects,plul=false) {
        if (objects.length == 0) return _responseError("Missing Input params: list of datepicker ids");
        _setDatePickerIdsMethod(objects)
        var defaultOptions = {
            autoclose: true,
            todayHighlight: true,
            orientation: "bottom left",
            disableTouchKeyboard: true,
            daysOfWeekDisabled: (plul)?"0,6":''
        };

        jQuery.each(datepickerIds, function( index, datepickerId ) {
            var options = jQuery.extend(defaultOptions, datepickerId['options']);
            jQuery(datepickerId['selector']).datepicker(options);

            jQuery('body').on('propertychange input', datepickerId, function() {
                jQuery(datepickerId['selector']).val(jQuery(datepickerId['selector']).val().replace(/[^\d\/]+/g,''))
            });
        });
    }

    var setDate = function(id, date) {
        jQuery(id).datepicker("setDate", date);
    }

    var getDate = function(id) {
        return jQuery(id).datepicker("getDate");
    }

    var _responseError = function(errMessage) {
        alert(errMessage)
    }

    return {
        initial: initialMethod,
        initialWithOptions: initialWithOptionsMethod,
        setDate: setDate,
        getDate: getDate
    }
})()

var timepickerModule = (function() {
    //Sample array
    // {
    //     selector: "#fromTime",
    //     options: {
    //         minuteStep: 15,
    //         showMeridian: false
    //     }
    // }
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

    var setTime = function(id, time) {
        jQuery(id).timepicker("setTime", time);
    }

    var getTime = function(id) {
        return jQuery(id).timepicker("getTime");
    }

    var _responseError = function(errMessage) {
        alert(errMessage)
    }

    return {
        initial: initialMethod,
        setTime: setTime,
        getTime: getTime
    }
})()

var dateRangePickerModule = (function() {
    var datepickerIds = [];
    var initialDateRange = function(dateFrom, dateTo){
        var minDate ;
        var maxDate;
        var mDate
        var j = jQuery.noConflict();
        j( dateFrom ).datepicker({
            onSelect: function() {
                setMinMaxDate(j(dateFrom),j(dateTo));
            }
        });

        j( dateTo ).datepicker({
            onSelect: function() {
                setMinMaxDate(j(dateFrom),j(dateTo));
            }
        });

        var tdate = new Date();
        var ddate = new Date(tdate.setDate(tdate.getDate() - 30));
        j( dateFrom ).datepicker("setDate", ddate);
        j( dateTo ).datepicker();
        j( dateTo ).datepicker("setDate",new Date());

        setOptions(j(dateFrom));
        setOptions(j(dateTo));
    }

    var dateRangeAMonth = function(dateFrom, dateTo, callback) {
        var minDate ;
        var maxDate;
        var mDate
        var j = jQuery.noConflict();
        j( dateFrom ).datepicker({
            onSelect: function() {
                minDate = j(dateFrom).datepicker("getDate");
                var month = new Date(j(dateFrom).val());
                var d = new Date(month.getFullYear(), month.getMonth() + 1, 0);
                maxDate = d.getFullYear()+ "/" + (d.getMonth() + 1) + "/" + d.getDate();
                j(dateTo).datepicker( "option", "minDate", minDate);
                j(dateTo).datepicker( "option", "maxDate", maxDate);
                callback('from');
            }
        });

        j( dateTo ).datepicker({
            onSelect: function() {
                setMinMaxDate(j(dateFrom),j(dateTo));
                callback('to');
            }
        });

        j( dateFrom ).datepicker();
        j( dateTo ).datepicker();

        setOptions(j(dateFrom));
        setOptions(j(dateTo));
    }

    var setMinMaxDate = function(dateFrom, dateTo){
        minDate = dateFrom.datepicker("getDate");
        maxDate = dateTo.datepicker("getDate");
        dateTo.datepicker( "option", "minDate", minDate);
        dateTo.datepicker( "option", "maxDate", maxDate);
    }

    var setOptions = function(element){
        element.datepicker("option", "showOn", 'both');
        element.datepicker("option", "buttonImageOnly", true);
        element.datepicker("option", "buttonImage", baseUrl+'/img/calendar.png');
        element.datepicker("option", "dateFormat", "yy/mm/dd");
    }

    var setDate = function(id, date) {
        jQuery(id).datepicker("setDate", date);
    }

    var getDate = function(id) {
        return jQuery(id).datepicker("getDate");
    }

    var _responseError = function(errMessage) {
        alert(errMessage)
    }

    return {
        dateRange: initialDateRange,
        dateRangeAMonth: dateRangeAMonth
    }
})();

//Plugin trigger seletor size changed

(function ($) {
    $.fn.sizeChanged = function (handleFunction) {
        var element = this;
        var lastWidth = element.width();
        var lastHeight = element.height();

        setInterval(function () {
            if (lastWidth === element.width()&&lastHeight === element.height())
                return;
            if (typeof (handleFunction) == 'function') {
                handleFunction({ width: lastWidth, height: lastHeight },
                            { width: element.width(), height: element.height() });
                lastWidth = element.width();
                lastHeight = element.height();
            }
        }, 100);


        return element;
    };
}(jQuery));

//for zoom detection
px_ratio = window.devicePixelRatio || window.screen.availWidth / document.documentElement.clientWidth;
$(window).resize(function(){isZooming();});
function isZooming(){
    var newPx_ratio = window.devicePixelRatio || window.screen.availWidth / document.documentElement.clientWidth;
    if(newPx_ratio != px_ratio){
        px_ratio = newPx_ratio;
        jQuery(".monthpicker, .datepicker").monthpicker( "hide" );
        jQuery(".ui-monthpicker, .ui-datepicker").datepicker( "hide" );
        jQuery(".ui-monthpicker, .ui-datepicker").hide();
        return true;
    }else{
        return false;
    }
}
