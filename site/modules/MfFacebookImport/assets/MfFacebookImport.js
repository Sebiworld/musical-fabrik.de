$(document).ready(function () {
    var collapseBtn = $('.mfimport-collapse-btn');
    $('.mfimport-collapsible').hide();

    if (collapseBtn.length) {
        collapseBtn.on('click', function () {
            $('.mfimport-collapsible').toggle();
        });
    }
});