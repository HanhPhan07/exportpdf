$(document).ready(function () {

    var loading = $('.save-self, .submit-self, .pic-staff, .btn-done-form').venobox({ closeBackground: 'rgba(68,68,68,0)', frameheight: '600' });
    // , .prev-mutual, .next-mutual

    /* Save self evaluation*/
    $('#saveSelf').click(function () {
        formSelf('saveSelf');
    });

    /* Submit self evaluation*/
    $('#submitSelf').click(function () {
        var point = 0;
        var textarea = 0;
        $('#pointSelf .point').each(function (index) {
            if ($(this).attr('previousValue') == 'checked' || $(this).attr('previousValue') == 'true') { point = 1 }
        });
        $('.textarea-self-form').each(function (index) {
            if ($(this).val()) { textarea = 1 }
        });
        if (textarea == 1 || point == 1) {
            if ($('#startPeriod').length) {
                formSelf('submitSelf');
            } else {
                loading.VBclose();
                Swal.fire({
                    text: 'The Evaluation system has not been open yet. Please come back later. Thank you!'
                })
                return;
            }
        } else {
            loading.VBclose();
            Swal.fire({
                text: 'Submit failed. Please fill out at least one of these fields.'
            })
            return;
        }
    });

    /* data selft evaluation */
    function formSelf(action) {
        var goal = $('#goal').val();
        var strength = $('#strength').val();
        var weakness = $('#weakness').val();
        var point = $('.point:checked').val();

        $.ajax({
            url: baseUrl + 'evaluationSystem/' + action,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            dataType: 'json',
            data: {
                'Goal': goal,
                'Strength': strength,
                'Weakness': weakness,
                'Point': point,
            },
            success: function (response) {
                if (response.Status == 'Success') {
                    loading.VBclose();
                    Swal.fire({
                        icon: 'success',
                        title: response.Title,
                    })
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                    })
                }
            }
        });
    }

    /* Event click next button  */
    $('#next-mutual').click(function (e) {
        e.preventDefault();
        var url = window.location.href;
        if (url.indexOf('?page') > -1) {
            var page = url.split('?page=')[1];
            var next = 0;
            next = Number(page) + 1;
            location.href = baseUrl + 'mutual-evaluation?page=' + next;
        } else {
            location.href = baseUrl + 'mutual-evaluation?page=2';
        }
    });

    /* Event click previous button  */
    $('#prev-mutual').click(function (e) {
        e.preventDefault();
        var url = window.location.href;
        if (url.indexOf('?page') > -1) {
            var page = url.split('?page=')[1];
            var prev = 0;
            prev = Number(page) - 1;
            if (page != "1") {
                location.href = baseUrl + 'mutual-evaluation?page=' + prev;
            } else {
                location.href = baseUrl + 'self-evaluation';
            }
        } else {
            location.href = baseUrl + 'self-evaluation';
        }
    });

    /* function navigation page*/
    function navPage(current) {
        $.ajax({
            url: baseUrl + 'evaluationSystem/mutual' + '?page=' + current,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                /* title */
                $('#titleForm').html(response.title);
                /* prev */
                var current = $('#currentPage').val();
                if (current == 1) {
                    $('#prevText').html('SELF-EVALUATION FORM');
                } else {
                    $('#prevText').html('MUTUAL EVALUATATION FORM (' + response.prev + ')');
                }
                /* next */
                if (response.next == null) {
                    $('.next-page').hide();
                } else {
                    $('#nextText').html('');
                    $('#nextText').html('MUTUAL EVALUATATION FORM (' + response.next + ')');
                    $('.next-page').show();

                }
                /* append form */
                let listing = '';
                const template = $('#rowForm').html();
                var i = 0;
                var point_html = '';
                var evaluation = '';
                response.data.map(function (val, index) {
                    i++;
                    point_html = '';
                    evaluation = '';
                    check_data = false;
                    if (i % 2 == 1) {
                        point_html = '<select class="cb-point" style="background-color:rgb(142, 193, 241); border:solid 0px; font-weight:700 !important;"><option value="-1" selected>Select</option>';
                    } else {
                        point_html = '<select class="cb-point" style="border:solid 0px; font-weight:bold !important;"><option value="-1" selected>Select</option>';
                    }
                    for ($n = 0; $n < 11; $n++) {
                        point_html += '<option style="font-weight:700 !important;" value="' + $n + '"';
                        if (val.TBLTOtherEvaluation.Point != null && $n == val.TBLTOtherEvaluation.Point) {
                            point_html += 'selected';
                        }
                        point_html += '>' + $n + '</option>';
                    }
                    point_html += '</select>';

                    if (i % 2 == 1) {
                        evaluation = '<textarea class="form-control evaluation" style="color:black; font-weight:700 !important; height:80px; margin-bottom:0px; margin-top:5px; background-color:rgb(142, 193, 241); border:solid 0px; border-top:solid 1px !important;" placeholder="Evaluation:" cols="20" rows="1">';
                        if (val.TBLTOtherEvaluation.Evaluation) {
                            evaluation += val.TBLTOtherEvaluation.Evaluation;
                            // $('#check_data').val('true');
                        }
                        evaluation += '</textarea>';
                    } else {
                        evaluation = '<textarea class="form-control evaluation" style="color:black; font-weight:700 !important; height:80px; margin-bottom:0px; margin-top:5px; border:solid 0px; border-top:solid 1px !important;" placeholder="Evaluation:" cols="20" rows="1">';
                        if (val.TBLTOtherEvaluation.Evaluation) {
                            evaluation += val.TBLTOtherEvaluation.Evaluation;
                            // $('#check_data').val('true');
                        }
                        evaluation += '</textarea>';
                    }

                    listing += template.replace(/__serial__/g, i)
                        .replace(/__staffID__/g, val.TBLMStaff.StaffID)
                        .replace(/__staffName__/g, val.TBLMStaff.StaffName)
                        .replace(/__point__/g, point_html)
                        .replace(/__evaluation__/g, evaluation);
                    // .replace(/__checkExistData__/g, check_data);
                    // checkExistData();
                })
                $('#formMutual').html("");
                $('#formMutual').append(listing);
                loading.VBclose();

            }
        });
    }

    /* scroll top */
    $(document).on('click', '#scrollTop', function (e) {
        e.preventDefault();
        $("div").animate({ scrollTop: 20 }, 1000); //desktop
        $('body,html').animate({ scrollTop: 20 }, 1000); //mobile
    });

    /* Save mutual evaluation */
    $('#saveMutual').click(function () {
        formMutual('saveMutual');
    });

    /* Submit mutual evaluation */
    $('#submitMutual').click(function () {
        if ($('#startPeriod').length) {
            formMutual('submitMutual');
        } else {
            loading.VBclose();
            Swal.fire({
                text: 'The Evaluation system has not been open yet. Please come back later. Thank you!'
            })
            return;
        }
    });

    /* data mutual form */
    function formMutual(action) {
        var data_form = [];
        $('#formMutual tr').each(function (index) {
            data_form.push({
                "StaffID_To": $(this).find('.staff-id').html(),
                "Point": $(this).find('.cb-point').val(),
                "Evaluation": $(this).find('.evaluation').val(),
            });
        });
        $.ajax({
            url: baseUrl + 'evaluationSystem/' + action,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            type: 'POST',
            data: { data_form },
            dataType: 'json',
            success: function (response) {
                if (response.Status) {
                    loading.VBclose();
                    Swal.fire({
                        icon: 'success',
                        title: response.Title,
                    })
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                    })
                }
            },
        });
    }

    /* Check/Uncheck radio point */
    $(document).on('click', '.point', function () {
        var previousValue = $(this).attr('previousValue');

        if (previousValue == 'checked') {
            $(this).prop("checked", false);
            $(this).attr('previousValue', false);
        } else {
            $('input[name="Point"]:radio').attr('previousValue', false);
            $(this).attr('previousValue', 'checked');
        }
    });

    /* clear self-form */
    $(document).on('click', '#clearSelfForm', function (e) {
        e.preventDefault();
        $('.textarea-self-form').val('');
        $('#pointSelf .point').each(function (index) {
            var previousValue = $(this).attr('previousValue');
            if (previousValue == 'checked' || previousValue == 'true') {
                $(this).prop("checked", false);
                $(this).attr('previousValue', false);
            }
            console.log(previousValue)
        });
    });

    /* clear mutual-form */
    $(document).on('click', '#clearMutualForm', function (e) {
        e.preventDefault();
        $('#formMutual select').each(function (index) {
            $(this).val(-1);
        });
        $('#formMutual textarea').each(function (index) {
            $(this).val('');
        });
    });


    // time left running
    var myVar = '';
    var timeLeft = function (toTime) {
        clearInterval(myVar);
        var countDownDate = Date.parse(toTime);
        myVar = setInterval(function () {
            var now = new Date().getTime();
            distance = countDownDate - now;
            if (distance > 0) {
                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                // Display the result in the element with id="demo"
                if (days >= 10) { clock = days + "d "; } else { clock = "0" + days + "d "; }
                if (hours >= 10) { clock += hours + "h "; } else { clock += "0" + hours + "h "; }
                if (minutes >= 10) { clock += minutes + "m "; } else { clock += "0" + minutes + "m "; }
                if (seconds >= 10) { clock += seconds + "s "; } else { clock += '0' + seconds + "s "; }
                document.getElementById("timeLeft").innerHTML = clock
            } else {
                $('.time-left').hide();
            }
        }, 1000)
    }

    window.onload = function () {
        var url = window.location.href;
        if (url.indexOf('?page') > -1) {
            if ($('#team_next').val()) {
                $('.next-page').show();
            } else {
                $('.next-page').hide();
            }
            var page = url.split('?page=')[1];
            if (page != "1") {
                $('#prevText').html('MUTUAL EVALUATATION FORM (' + $('#team_prev').val() + ')');
            } else {
                $('#prevText').html('SELF-EVALUATION FORM');
            }
        } else {
            $('#prevText').html('SELF-EVALUATION FORM');
        }
        $('#nextText').html('MUTUAL EVALUATATION FORM (' + $('#team_next').val() + ')');
        if ($('#endPeriod').length) {
            timeLeft($('#endPeriod').val());
            $('.time-left').show();
            $('.evaluation-id').show();
            $('.evaluation-title').show();
        } else {
            $('.time-left').hide();
            $('.evaluation-id').hide();
            $('.evaluation-title').hide();
        }
    }
});
