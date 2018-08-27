$(document).ready(function () {

    $("#calendar_after").click(function (e) { 
            e.preventDefault();
            var mes =  $('#calendar_mes_dinamic').val() ; 
            if (parseFloat(mes) < 12) {
              $('#calendar_mes_dinamic').val(parseFloat(mes) + 1)  ; 
               load_fn_calendar_dinamic();                   
            }    

    });

    $("#calendar_before").click(function (e) { 
            e.preventDefault();
            var mes =  $('#calendar_mes_dinamic').val() ;     
            if (parseFloat(mes) > 1) {
              $('#calendar_mes_dinamic').val(parseFloat(mes) - 1)  ;  
               load_fn_calendar_dinamic();                  
            } 

    });


load_fn_calendar_dinamic();
function load_fn_calendar_dinamic() {
    
     $("#loading_list").show();
      
      var wp_ajax = $('#url_content_calendar').val();
      var mes = $('#calendar_mes_dinamic').val();
      var post_type_name = $('#cale_post_type_name').val();
      var taxo_post_type = $('#cale_taxo_post_type').val();
      var acf_name_date_event = $('#cale_acf_name_date_event').val();                                  

      $.ajax({
          type: "POST",
          url: wp_ajax,
          data: {
              action: 'calendar_next_prev',        
              mes: mes,
              post_type_name: post_type_name,
              taxo_post_type: taxo_post_type,
              acf_name_date_event: acf_name_date_event                  
          },
          dataType: "html",
          success: function (data) {
                $('#calendar_box').html(data);
                                       
                
            
                 $("#loading_list").hide();    
                 
          },
      });
  }
});