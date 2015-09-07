function lazyload_grid_log(txt)
{
    //console.log(txt);
}

function lazyload_grid_footerlog(txt)
{
    //$$('.footer-menu a').html(txt);
}

function smekta(pattern,vars) {
    var key,re;
    
    for (key in vars)
    {
        if (vars[key]==null)  vars[key]='';
        
        if (typeof(vars[key])=='object') {
            re=new RegExp('\\[loop:'+key+'\\](.|[\r\n])+\\[endloop:'+key+'\\]',"g");
            var loop=pattern.match(re);
            if (loop!=null) {
                
                for (var l=0;l<loop.length && l<7;l++)
                {
                    var loopstring=loop[l];
                    var loopcontents=loop[l].substr(7+key.length);
                    loopcontents=loopcontents.substr(0,loopcontents.length-10-key.length);
                    var loopresult='';
                    for (var k=0;k<vars[key].length;k++)
                    {
                        loopresult+=smekta(loopcontents,vars[key][k]);
                    }
                    pattern=pattern.replace(loopstring,loopresult);
                }
                
            }
                
        }
        
        
        re=new RegExp('\\[if:'+key+'\\](.|[\r\n])+\\[endif:'+key+'\\]',"g");
        if (vars[key].length==0 || vars[key]==null || vars[key]=='0') pattern=pattern.replace(re,'');
        
        re=new RegExp('\\[if:!'+key+'\\](.|[\r\n])+\\[endif:!'+key+'\\]',"g");
        if (vars[key].length>0 || vars[key]) pattern=pattern.replace(re,'');
        
        
        re=new RegExp('\\['+key+'\\]',"g");
        pattern=pattern.replace(re,vars[key]);
        
        
        pattern=pattern.replace('[if:'+key+']','');
        pattern=pattern.replace('[endif:'+key+']','');
        pattern=pattern.replace('[if:!'+key+']','');
        pattern=pattern.replace('[endif:!'+key+']','');        
        
    }
    
    return pattern;

}

var lazyload_grid_limit;
var lazyload_grid_offset=0;
var lazyload_grid_form;
var lazyload_grid_template;
var lazyload_grid_results;
var lazyload_grid_ajax='';
var lazyload_grid_lazyload=false;
var lazyload_grid_winheight=0;




function lazyload_grid_load()
{
     
    txt=$$('#'+lazyload_grid_form).serialize();
    dbg='loading offset '+lazyload_grid_offset+', limit '+lazyload_grid_limit;
    lazyload_grid_log(dbg);
    lazyload_grid_footerlog(dbg);
    
    var d = new Date();
    var url=lazyload_grid_ajax+'?offset='+lazyload_grid_offset+'&'+txt;
    
    $$get(url,function (r) {
        lazyload_grid_log(r);
        $$('.lazyload_grid_scroll_to_wait').remove();
        
        
        var html=$$('#'+lazyload_grid_template).html();
        
        if (lazyload_grid_offset==0) {
            html2=r.options.results+' ('+r.x_system.total_time+' s)';
            if (typeof(r.options.change)!='undefined' && r.options.change.length) {
                html2='<h2>'+r.options.change+'</h2>'+html2;
            }
    
            var ul='<ul><li rel="0" class="'+(!r.options.totalPrice || r.options.totalPrice==0?'active':'')+'">Ceny za osobę</li><li rel="1" class="'+(r.options.totalPrice==1?'active':'')+'">Ceny łączne</li></ul>';
            html2='<div class="row lazyload_grid_results">'+ul+html2+'</div>';
            $$(html2).appendTo('#'+lazyload_grid_results).fadeIn(200);
        }
        data=r.data;
        for(i=0;i<data.length;i++)
        {
            html2=smekta(html,data[i]);
            $$(html2).appendTo('#'+lazyload_grid_results).fadeIn(200*(i+1));
            //lazyload_grid_offset++;
            
        }
        lazyload_grid_offset=r.options.next_offset;
        

        if (lazyload_grid_lazyload && data.length>0) {
            $$('#'+lazyload_grid_results).append('<div class="lazyload_grid_scroll_to"></div>');
            $$(window).scroll(lazyload_grid_scroll); 
            lazyload_grid_log('waiting to scroll');
            
            post_lazyload();
        }

        
        lazyload_grid_log('data loaded ('+data.length+'), offset->'+lazyload_grid_offset);
        
        
    });   
}


function lazyload_grid_reload()
{

    $$('#'+lazyload_grid_results).html('').append('<div class="lazyload_grid_scroll_to_wait"></div>');
    lazyload_grid_offset=0;
    lazyload_grid_load();
}

function lazyload_grid_scroll()
{
    var scroll_to = $$('.lazyload_grid_scroll_to');

    if (typeof(scroll_to.get(0))=='undefined') return; 
    
    var hT = scroll_to.offset().top,
        hH = scroll_to.outerHeight(),
        wH = lazyload_grid_winheight,
        wS = $$(window).scrollTop();
        
    
    var h3=hT+hH-wH;
    lazyload_grid_footerlog(wS+' > '+h3+' : '+(wS > h3));
      
    if (1.3*wS > h3){
        lazyload_grid_log('scroller reached');
        $$('.lazyload_grid_scroll_to').addClass('lazyload_grid_scroll_to_wait').removeClass('lazyload_grid_scroll_to');
        lazyload_grid_load();
    }
}



function lazyload_grid(form,template,results,limit,ajax,lazyload,start)
{
    lazyload_grid_limit = limit;
    lazyload_grid_ajax = ajax;
    lazyload_grid_form = form;
    lazyload_grid_template = template;
    lazyload_grid_results = results;
    lazyload_grid_lazyload = lazyload;
    lazyload_grid_winheight=$$(window).height();
    
    
    $$('#'+lazyload_grid_results).html('');
    
    if (lazyload) {
        
        $$(window).scroll(lazyload_grid_scroll);      
    }
    
    

    if (start) lazyload_grid_load();


}

