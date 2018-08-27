$(document).ready(function() {
    $( ".hide" ).hide();

    $(".btn-add-box").click(function(){ 
        var html = $(".clone").html();
        $(".increment").after(html);
    });

    $("body").on("click",".btn-delete-box",function(){ 
        $(this).parents(".control-group").remove();
    });

});