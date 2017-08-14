$(function () {

    // datepicker
    $('.datepicker').datepicker({
        format: dp_format,
        weekStart: 1
    });

    // tooltips
    $('.bs-tooltip, [data-toggle="tooltip"]').tooltip();

});

