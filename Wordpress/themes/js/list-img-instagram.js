$(document).ready(function () {

    var token_insta = $("#token_id_instagram").val();
    var num_photos = $("#count_img_per_list_img_instagram").val();

        if (token_insta != "") {
                
                $.ajax({
                    url: 'https://api.instagram.com/v1/users/self/media/recent',
                    dataType: 'jsonp',
                    type: 'GET',
                    data: {access_token: token_insta, count: num_photos},
                    success: function(data){     
                        $('#next_url_list_img_instagram').val(data.pagination.next_url);
                        for( x in data.data ){
                            $('#list_img_instagram').append('<li class="instagram-widget__item" > <a href="'+ data.data[x].link +'" target="_black"  class="instagram-widget__link"><img src="'+data.data[x].images.low_resolution.url+'"  width="93" height="93" ></a></li>');
                            
                        }
                    },
                    error: function(data){
                        console.log(data);
                    }
                });
        }
   

        $(document).on('click', '#btn_next_url_list_img_instagram', function(event) {
            var url_next =   $('#next_url_list_img_instagram').val();
            // alert(url_next);
            // console.log(url_next);                 
            $.ajax({
                url: url_next,
                dataType: 'jsonp',
                type: 'GET',
                data: {access_token: token_insta, count: num_photos},
                success: function(data){
                    // console.log(data);     
                    $('#next_url_list_img_instagram').val(data.pagination.next_url);                                                            
                    for( x in data.data ){
                        // console.log();
                        $('#list_img_instagram').append('<li> <a href="'+ data.data[x].link +'" target="_black" ><img src="'+data.data[x].images.low_resolution.url+'"></a></li>');
                        
                    }
                },
                error: function(data){
                    console.log(data);
                }
            });
        });


});