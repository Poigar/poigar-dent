function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

function load_pattern(user_id,){
    var pattern_wrapper = document.getElementById('pattern_'+user_id);
    
    var hours = pattern_wrapper.getElementsByClassName('hour');
    
    pattern = $('.hidden_pattern_'+user_id).html();

    console.log(pattern);
    for(var i=0; i<hours.length; i++){
        if(pattern[i] == "1"){
            $(hours[i]).addClass('hour--active');
        } 
    }
}


function save_pattern(user_id){
    var pattern_wrapper = document.getElementById('pattern_'+user_id);

    var hours = pattern_wrapper.getElementsByClassName('hour');
    
    var pattern = "";

    for(var i=0; i<hours.length; i++){
        if($(hours[i]).hasClass('hour--active')) pattern = pattern+"1";
        else pattern = pattern +"0";
    }

    window.location = "edit_employee_pattern_action/"+user_id+"?pattern="+pattern;
}