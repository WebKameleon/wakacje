

var proceed_prority=0;



$(function(){
    $('#kiedyMszaForm .date').pickadate({
        onSet:function() {
            proceed_prority=2;
            grid_start('datepicker',2);
        },
        format: 'dddd, dd mmm yyyy',
        selectYears: false,
    });

    $('#kiedyMszaForm .time').pickatime({
        onSet:function() {
            proceed_prority=2;
            grid_start('timepicker',2);
        },        
        format: 'HH:i',
        formatSubmit: 'HH:i',
        min: [6,0],
        max: [21,0],
        interval: 60
    });
    
    setTimeout(function() {
        if (proceed_prority==0) grid_start('timer',0);
    },400);
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
            $('#geo').val(pos.coords.latitude+','+pos.coords.longitude);
            $('#navigator_missing').attr('found',true).fadeOut();
            
            if (proceed_prority<=1) { 
                proceed_prority=1;
                grid_start('navigator',1);
            }
            
            var geocoder = new google.maps.Geocoder();
            
            geocoder.geocode( { 'address': $('#geo').val()}, function(results, status) {
                //console.log(results);
                if(status=='OK') $('#where').val(results[0].formatted_address);
                
            });
            
        });
    }
    
    
    var input = document.getElementById('where');
    var options = {
      types: ['geocode'],
      componentRestrictions: {country: 'pl'}
    };    
    autocomplete = new google.maps.places.Autocomplete(input,options);
    
    google.maps.event.addListener(autocomplete, 'place_changed', function() {
        var place = autocomplete.getPlace();
        $('#geo').val(place.geometry.location.lat()+','+place.geometry.location.lng());

        proceed_prority=2;
        grid_start('autocompleted',2);
    });
    
    
    
});



