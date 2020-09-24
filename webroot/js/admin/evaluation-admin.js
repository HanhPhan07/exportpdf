$(window).on("load resize ", function () {
    var scrollWidth = $('.tbl-content').width() - $('.tbl-content table').width();
    $('.tbl-header').css({ 'padding-right': scrollWidth });
}).resize();

$("#startPeriod").datepicker({
    defaultDate: "+1w",
    changeMonth: true,
    numberOfMonths: 1,
    dateFormat: "yy/mm/dd"
})
$("#endPeriod").datepicker({
    defaultDate: "+1w",
    changeMonth: true,
    numberOfMonths: 1,
    dateFormat: "yy/mm/dd"
})

$(document).ready(function () {
    $('.pic-staff').venobox({ closeBackground: 'rgba(68,68,68,0)', frameheight: '600' });

    // $('.btn-sort').on('click', function (e) {
    //     e.preventDefault();
    //     var link = '';
    //     link = baseUrl + 'admin/evaluation/properties-' + $(this).closest('div').find('.properties').val() + '/id-' + $('#evaluatePeriod').val();
    //     location.href = link;
    // })

    $('.properties-self').on('change', function () {
        var link = '';
        link = baseUrl + 'admin/evaluation/properties-' + $(this).val() + '/id-' + $('#evaluatePeriod').val();
        location.href = link;
    })

    $('.properties-mutual').on('change', function () {
        const staffId = $(this).closest('div').find('.staffid-to').val();
        var link = '';
        link = baseUrl + 'admin/mutual-evaluation-' + staffId + '/properties-' + $(this).val() + '/id-' + $('#evaluatePeriod').val();
        location.href = link;
    })

    $('.btn-search').on('click', function (e) {
        e.preventDefault();
        var link = '';
        link = baseUrl + 'admin/evaluation/search-' + $(this).closest('div').find('.input-search').val() + '/id-' + $('#evaluatePeriod').val();
        location.href = link;
    });

    $('.btn-select-evaluation-period').on('click', function (e) {
        e.preventDefault();
        location.href = baseUrl + 'admin/evaluation/start-' + moment($('#fromPeriod').val()).format('YYYYMMDD') + '/end-' + moment($('#toPeriod').val()).format('YYYYMMDD');
    });

    /* start events of evaluation */
    /* click "Evaluation System" */
    $("#btnListPeriod").click(function (event) {
        event.preventDefault();
        getCurrentPeriod();
        $('#modalListPeriod').modal();
    });
    /* delete period */
    $(document).on('click', '.del-period', function (e) {
        e.preventDefault();
        var id = $(this).closest('p').find('.id-period').html();
        if ($("#listSelfPeriods").length) {
            deletePeriod(id, 'self');
        } else {
            deletePeriod(id, 'mutual');
        }
    })
    /* end events of evaluation */

    $('#evaluatePeriod').on('change', function () {
        var href = window.location.href;
        if (href.indexOf('/mutual-evaluation') == -1) {
            location.href = baseUrl + 'admin/evaluation/id-' + $(this).val();
        } else {
            location.href = baseUrl + 'admin/mutual-evaluation/id-' + $(this).val();
        }
    });

    $('#backTableSelf').on('click', function(e){
        e.preventDefault()
        var href = window.location.href;
        location.href = baseUrl + 'admin/evaluation/id-' + $('#evaluatePeriod').val();
    })

    $('.select-staff-id').on('change', function () {
        var staffID = $(this).val();
        if (staffID != -1) {
            location.href = baseUrl + 'admin/mutual-evaluation-' + staffID + '/id-' + $('#evaluatePeriod').val();
        }
    });

    $('.previous-mutual').on('click', function (e) {
        e.preventDefault();
        $(this).attr("disabled", true);
        var staffID = $(this).closest('div').find('.previous-StaffID').val();
        location.href = baseUrl + 'admin/mutual-evaluation-' + staffID + '/id-' + $('#evaluatePeriod').val();
    })

    $('.next-mutual').on('click', function (e) {
        e.preventDefault();
        $(this).attr("disabled", true);
        var staffID = $(this).closest('div').find('.next-StaffID').val();
        location.href = baseUrl + 'admin/mutual-evaluation-' + staffID + '/id-' + $('#evaluatePeriod').val();
    })
});

//Get the button
var topBtn = document.getElementById("topBtn");
var downBtn = document.getElementById("downBtn");
// When the user scrolls down 20px from the top of the document, show the button
window.onscroll = function () { scrollFunction() };
scrollingElement = (document.scrollingElement || document.body)

function scrollFunction() {
    if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
        topBtn.style.display = "block";
        downBtn.style.display = "none";
    } else {
        downBtn.style.display = "block";
        topBtn.style.display = "none";
    }
}
// When the user clicks on the button, scroll to the top of the document
function topFunction() {
    $(scrollingElement).animate({
        scrollTop: 0
    }, 500);
    downBtn.style.display = "block";
    topBtn.style.display = "none";
}

function bottomFunction() {
    $(scrollingElement).animate({
        scrollTop: document.body.scrollHeight
    }, 500);
    topBtn.style.display = "block";
    downBtn.style.display = "none";
}
// END BUTTON

/* setting current period */
function getCurrentPeriod() {
    const url = window.location.href;
    $.ajax({
        type: 'get',
        url: baseUrl + 'admin/evaluation/evaluatePeriodList',
    })
        .done(function (response) {
            /* append other periods */
            var is_empty = 1;
            var id_current = '';
            if (response.period) {
                var list = '';
                if (url.indexOf('/id-') > -1) {
                    var id_current = window.location.href.split('/id-')[1];
                }
                $.each(response.period, function (index, value) {
                    if (url.indexOf('/admin/mutual-evaluation') > -1) {
                        if (value.DeletedOnMutual == 0) {
                            if (id_current != '' && value.EvaluationID != id_current) {
                                list += '<p class="closed-period">' + value.EvaluationID;
                                +'<span class="del-period" style="color:red; margin-left:6px;cursor:pointer;">Delete</span>'
                                    + '<span class="id-period" hidden>' + value.EvaluationID + '</span>'
                                    + '</p>';
                                is_empty = 0;
                            }
                        }
                    } else {
                        if (value.DeletedOnSelf == 0) {
                            if (id_current != '' && value.EvaluationID != id_current) {
                                list += '<p class="closed-period">' + value.EvaluationID;
                                +'<span class="del-period" style="color:red; margin-left:6px;cursor:pointer;">Delete</span>'
                                    + '<span class="id-period" hidden>' + value.EvaluationID + '</span>'
                                    + '</p>';
                                is_empty = 0;
                            }
                        }
                    }
                });
            }
            if (is_empty == 1) {
                var list = 'Empty list';
            }
            if ($("#listSelfPeriods").length) {
                $("#listSelfPeriods").html('');
                $("#listSelfPeriods").append(list);
            } else {
                $("#listMutualPeriods").html('');
                $("#listMutualPeriods").append(list);
            }
        });
}

/* delete period */
function deletePeriod(id, table) {
    // var form = event.target.form; // storing the form
    swal({
        title: "Are you sure you want to delete this period?",
        icon: "error",
        buttons: true,
        dangerMode: true,
    })
        .then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    type: 'post',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    url: baseUrl + 'admin/evaluation/deletePeriod',
                    data: { 'id': id, 'table': table }
                })
                    .done(function (response) {
                        if (response.success) {
                            var is_empty = 1;
                            var id_current = window.location.href.split('/id-')[1];
                            if (response.dt_old) {
                                var list = '';
                                $.each(response.dt_old, function (index, value) {
                                    if (value.EvaluationID != id_current) {
                                        list += '<p class="closed-period">' + value.EvaluationID
                                            + '<span class="del-period" style="color:red; margin-left:6px;cursor:pointer;">Delete</span>'
                                            + '<span class="id-period" hidden>' + value.EvaluationID + '</span>'
                                            + '</p>';
                                        is_empty = 0;
                                    }
                                });
                            }
                            if (is_empty == 1) {
                                var list = 'Empty list';
                            }
                            if ($("#listSelfPeriods").length) {
                                $("#listSelfPeriods").html('');
                                $("#listSelfPeriods").append(list);
                            } else {
                                $("#listMutualPeriods").html('');
                                $("#listMutualPeriods").append(list);
                            }
                            swal("This period has been deleted!", {
                                icon: "success",
                            })
                                .then((reload) => {
                                    if (reload) {
                                        window.location.reload(true);
                                    }
                                });
                        }
                    });
            }
        });
}

function showStaffListToSelect(id) {
    $(".select-staff-id").html('<option value="-1">StaffID</option>')
    $.ajax({
        url: baseUrl + "admin/evaluation/staffIdList",
        headers: {
            'X-CSRF-Token': csrfToken
        },
        type: 'get',
        success: function (res) {
            // if (res.staffs) {
            //     var index;
            //     for (index = 0; index < res.staffs.length; index++) {
            //         var staffId = res.staffs['ID'][index];
            //         if (id == staffId) {
            //             $(".select-staff-id").append('<option value="' + staffId + '" selected>' + staffId +  '</option>');
            //         } else {
            //             $(".select-staff-id").append('<option value="' + staffId + '">' + staffId + '</option>');
            //         }
            //     }
            // }
            $.each(res.staffs,function(index,value){
                if(id == value.ID){
                    $(".select-staff-id").append('<option value="' + value.ID + '" selected>' + value.ID + ' - ' + value.Name +  '</option>');
                } else {
                    $(".select-staff-id").append('<option value="' + value.ID + '">' + value.ID + ' - ' + value.Name + '</option>');
                }
            })
        },
        error: function (res) {
            console.log(res);
        }
    });
}

function appendEveluatePeriod(id) {
    const url = window.location.href;
    $.ajax({
        url: baseUrl + "admin/evaluation/evaluatePeriodList",
        headers: {
            'X-CSRF-Token': csrfToken
        },
        type: 'get',
        success: function (response) {
            $.each(response.period, function (index, value) {
                if (url.indexOf('/admin/mutual-evaluation') > -1) {
                    if (value.DeletedOnMutual == 0) {
                        var option = '';
                        var selected = '';
                        if (id == value.EvaluationID) {
                            selected = "selected";
                        }
                        option = '<option value="' + value.EvaluationID + '"' + selected + '>' + value.EvaluationID + '</option>';
                        $('#evaluatePeriod').append(option);
                    }
                } else {
                    if (value.DeletedOnSelf == 0) {
                        var option = '';
                        var selected = '';
                        if (id == value.EvaluationID) {
                            selected = "selected";
                        }
                        option = '<option value="' + value.EvaluationID + '"' + selected + '>' + value.EvaluationID + '</option>';
                        $('#evaluatePeriod').append(option);
                    }
                }
            })
        },
        error: function (response) {
            console.log(response);
        }
    });
}

function setHeader() {
    var url_string = window.location.href;
    var id = 0;
    if (url_string.indexOf('/id-') > -1) { id = url_string.split('/id-')[1]; }
    appendEveluatePeriod(id);
    if ($('.properties-self').length || $('.properties-mutual').length) {
        /* sort */
        if (url_string.indexOf('/properties') > -1) { /* check url contain sort */
            var col = url_string.split("/properties-")[1];
            col = col.substr(0, col.indexOf('/'));
            $('.properties-self').val(col);
            $('.properties-mutual').val(col);
            /* search */
        } else if (url_string.indexOf('/search-') > -1) {
            $('.properties-self').val('StaffID');
            var key = url_string.split('/search-')[1];
            key = key.substr(0, key.indexOf('/'));
            $('.input-search').val(key);
        } else {
            $('.properties-mutual').val('Point');
            $('.properties-self').val('StaffID');
        }
    }
    var id_staff = 0;
    if ($('.select-staff-id').length) {
        if (url_string.indexOf('/mutual-evaluation-') > -1) {
            var staffid = url_string.split('/mutual-evaluation-')[1];
            id_staff = staffid.substr(0, staffid.indexOf('/'));
        }
        showStaffListToSelect(id_staff);
    }
}
window.onload = setHeader();
