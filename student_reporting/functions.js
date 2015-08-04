$(document).ready(function () {
    // Get the courseid now, because we'll be needing it
    var courseid = $("input[name='id']").val();

    $('.userlink').click(function(index) {
        var userid = $(this).attr('id').split('_')[1];
        var dynamic = $('#report_user_' + userid);        
        if(dynamic.children().length == 0) {
            $.get('rpc.php', {id: courseid, userid: userid}, function(data) {
                dynamic.html(data);
            });
        }
        dynamic.toggle();
        return false;
    });

    $("input[name^='section_']").change(function() {
        var checked = $(this).attr('checked');
        var elem = $('#' + $(this).attr('name'));
        if(checked) {
            var checks = elem.children("li").children("span").children("input");
            checks.removeAttr('checked');
            elem.hide();
        } else elem.show(); 
    });
});
