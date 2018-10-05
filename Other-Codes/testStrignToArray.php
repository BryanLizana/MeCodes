<?php 
 
    $data['drivers'][0]['driver_first_name'] = "123";
echo '<pre>'; var_dump( json_encode($data) ); echo '</pre>'; die;/***HERE***/ 

    //    "{"drivers":{"test":{"driver_first_name":"123"}}}"
    //    "{"drivers":{"0":{"driver_last_name":"123"}}}"
    //     {"drivers":[{"driver_first_name":"123"}]}



    $array = [
        'drivers.0.driver_last_name' => 'client.client_last_name',
        'index.value'=>'newindex.value',
        'value'=>'newindex2.value'
    ];
  

    function stringToArray($string)
    {
          $list_one = explode(".",$string);
          $end_String = "";
          foreach ($list_one as  $value) {
              if (is_numeric($value)) {
              $end_String .=']';               
              }else {
              $end_String .='}';               
              }
          }
      
          $string_uni = implode('":{"', $list_one) ;         
          $string_uni = '{"' . $string_uni.'":"test_value"'. $end_String;
          $string_uni = str_replace('{"0":','[',$string_uni); //Mejorar, porque ahora sólo funciona con "0" xd
          $obj = json_decode($string_uni,true);
          
          return $obj;
    } 
    echo '<pre>'; var_dump(  stringToArray("index.subindex.value'") ); echo '</pre>'; die;/***HERE***/ 


    foreach ($array as $key => $value) {
        $keyNew = stringToArray($value); 
        echo '<pre>'; var_dump( $keyNew ); echo '</pre>'; die;/***HERE***/ 
        $valueNew = stringToArray($key);
        // $data['client']['client_first_name'] = $data['drivers'][0]['driver_first_name'];
        $keyNew = $valueNew;
        $result[] = $keyNew;
    }


    echo '<pre>'; var_dump(  $result ); echo '</pre>'; die;/***HERE***/ 
