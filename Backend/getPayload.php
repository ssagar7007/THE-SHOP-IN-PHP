<?php
 function getPayload($secretValue){
    $headers = getallheaders();
    $jwt = $headers['Authorization'];
    $is_jwt_valid = is_jwt_valid($jwt,$secretValue);
    if($is_jwt_valid === TRUE)
    {
         $decoded_payload = decode_jwt($jwt,$secretValue);
         return $decoded_payload;
    }
    else
    {
        return null;
    }
 }
?>