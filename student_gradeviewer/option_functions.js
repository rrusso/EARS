$(document).ready(function() {
    $('.student_list').fadeIn(100).delay(2000).hide('slow');

    function bind_selectors(prefix, value) {
        $("a[class^='"+prefix+"|']").click(function() {
            var key = $(this).attr('class').split('|')[1];

            $("input[name^='opt|" + key + "|']").attr('checked', value);
            return false;
        });
    }

    bind_selectors('all', true);
    bind_selectors('none', false);
});
