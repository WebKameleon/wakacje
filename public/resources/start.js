
lazyload_grid('webkameleon_holidays_form','webkameleon_holidays_template','webkameleon_holidays_results',15,holidays_url+'holidays',true);
$('#webkameleon_holidays_form a').click(lazyload_grid_reload);

$.get(holidays_url+'template/placeholder',function(data) {
    $('#webkameleon_holidays_form input').attr('placeholder',data.template).focus();
    
});