$(document).ready(function() {

    /////////////////////////
    // Выбор нужного пунтка меню
    /////////////////////////

    var url = window.location.href.split('#')[0].split('?')[0];
    var element = $('ul.sidebar-menu a').filter(function () {
        return url.startsWith(this.href);
    });
    $(element).parentsUntil('ul.sidebar-menu', 'li').addClass('active');

    /////////////////////////
    // Настройки select2
    /////////////////////////

    $('select').select2({
        theme: "bootstrap",
        language: "ru"
    });

    $("#_per_page").on('select2:selecting', function (e) {
        window.location = $(e.params.args.data.element).attr('data-url');
    });

    $("[role=_filter]").on('select2:selecting', function (e) {
        window.location = $(e.params.args.data.element).val();
    });

    /////////////////////////
    // Прочее
    /////////////////////////

    $('input[type=checkbox]').iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue'
    });

    $(".datepick-input").datepicker({
        isRTL: false,
        format: 'yyyy-mm-dd',
        autoclose: true,
        language: "ru-RU"
    });
});