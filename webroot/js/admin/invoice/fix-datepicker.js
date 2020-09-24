/* range datepicker */
function rangeDate() {
    var dateFormat = "yy/mm/dd",
        from = $("#startPeriod")
            .datepicker({
                defaultDate: "+1w",
                changeMonth: true,
                numberOfMonths: 1,
                dateFormat: "yy/mm/dd",
                minDate: 0,
            })
            .on("change", function () {
                to.datepicker("option", "minDate", getDate(this));
            })
            .on("click", function () {
                optimizePositionDatePicker('startPeriod');
            }),
        to = $("#endPeriod")
            .datepicker({
                defaultDate: "+1w",
                changeMonth: true,
                numberOfMonths: 1,
                dateFormat: "yy/mm/dd",
                minDate: 0,
            })
            .on("change", function () {
                from.datepicker("option", "maxDate", getDate(this));
            })
            .on("click", function () {
                optimizePositionDatePicker('endPeriod');
            });

    function getDate(element) {
        var date;
        try {
            date = $.datepicker.parseDate(dateFormat, element.value);
        } catch (error) {
            date = null;
        }

        return date;
    }
}

/* fix position datepicker when resize */
$(window).resize(function () {
    $("#ui-datepicker-div").css("display", "none");
});
function getPosition(element) {
    var rect = element.getBoundingClientRect();
    return { x: rect.left, y: rect.top };
}
function optimizePositionDatePicker(str_id_datepicker) {
    var element = document.getElementById(str_id_datepicker);
    var pos = getPosition(element);
    $("#ui-datepicker-div").css("left", pos.x);
    $("#ui-datepicker-div").css("top", pos.y + 40);
    $("#ui-datepicker-div").css("display", "block");
}
