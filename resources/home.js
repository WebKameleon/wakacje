var social_m="mailto:?subject=Znalezione+wakacje&body="+encodeURIComponent(location.href);
var social_f="https://www.facebook.com/sharer/sharer.php?t=Znalezione+wakacje&u="+encodeURIComponent(location.href);

    $('.bkgimg').fadeIn(500);
    
    function bkgimg_animation()
    {
        var top=parseInt($('#webkameleon_holidays_form').css('top'));
        var newTop=30;
        if (top!=newTop) {
            var m=top-newTop;
            $('#webkameleon_holidays_form').css('top',newTop+'px');
            $('.bkgimg').css('margin-top','-'+m+'px');
            $('#webkameleon-top-demo').height('140px');
        }
        return false;        
    }
    
    
    function webkameleon_demo_form()
    {
        var helpFound=$('#webkameleon_holidays_form');
        if (helpFound.length==0) {
            setTimeout(webkameleon_demo_form,200);
        } else {
            $('#webkameleon_holidays_form').submit(bkgimg_animation);
            $('#webkameleon_holidays_form a.go').click(bkgimg_animation);
            
            $('#webkameleon_holidays_form').append('<div class="holiday_social"><a class="f"></a><a class="m"></a></div>');
            $('#webkameleon_holidays_form .holiday_social a.m').click(function() {
                $(this).attr('target','_blank');
                $(this).attr('href',social_m);
            });
            
            $('#webkameleon_holidays_form .holiday_social a.f').click(function() {
                window.open(social_f, '', 'width=520, height=300, toolbar=no, scrollbars=no, resizable=yes');                
                return false;
            });
            
            
            //"
        
        }
    }
    webkameleon_demo_form();