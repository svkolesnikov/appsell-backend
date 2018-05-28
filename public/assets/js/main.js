$(document).ready(function() {

    // выбор нужного пунтка меню
    var url = window.location.href.split('#')[0].split('?')[0];
    var element = $('ul.sidebar-menu a').filter(function () {
        return url.startsWith(this.href);
    });
    $(element).parentsUntil('ul.sidebar-menu', 'li').addClass('active');

    $('select').select2({
        theme: "bootstrap",
        language: "ru"
    });

    $('input[type=checkbox]').iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue'
    });

    $(".datepick-input").datepicker({
        isRTL: false,
        format: 'dd.mm.yyyy',
        autoclose: true,
        language: "ru-RU"
    });

    $("#_per_page").on('select2:select', function (e) {
        window.location = e.params.data.id;
    });

    $("[role=_filter]").on('select2:select', function (e) {
        window.location = e.params.data.id;
    });
});