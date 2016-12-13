 // /public/js/custom.js
jQuery(function($) { 
    $(".Linkscontainer ul li ul").css("display", "none");
                $(".Linkscontainer a").click(function (e) {
                    if ($(this).attr("href") == '#') {
                        e.preventDefault();
                        $(this).closest('li').children('ul').toggle();
                    }
                });
});