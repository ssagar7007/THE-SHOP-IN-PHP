<?php

function paymentToken($gateway){
    try {
        $clientToken = $gateway->clientToken()->generate();
       
    } catch (Exception $e) {
        
        error_log($e->getMessage());
        $msg = $e->getMessage();
        http_response_code(500);
        echo "An error occurred while generating the client token.$msg ";
    }
    $response = array(
        'clientToken' =>$clientToken ,
        'success' => true,
    );
    
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');
    echo $jsonResponse;   
}
function paymentStatus($gateway,$conn,$secretValue){
    $data = json_decode(file_get_contents('php://input'), true);
    $headers = getallheaders();
    $jwt = $headers['Authorization'];
    $is_jwt_valid = is_jwt_valid($jwt,$secretValue);
    if($is_jwt_valid === TRUE){
        $decoded_payload = decode_jwt($jwt,$secretValue);
        if ($decoded_payload !== null) {
            $emailid = $decoded_payload['email'];
    }

    $cart = $data['cart'];
    $nonce = $data['nonce'];
    $total = 0;
    foreach($cart as $item){
        $total += $item['price'];
    }
    
    try {
        $result = $gateway->transaction()->sale([
            'amount' => $total,
            'paymentMethodNonce' => $nonce,
            'options' => [
                'submitForSettlement' => true,
            ],
        ]);
    
        if ($result->success) {
            $cart2 = json_encode($cart);
            $sql2 = "INSERT INTO ecommerce.orderdetail (products,payment,buyer,status) VALUES ('$cart2','$result','$emailid','Not Process')";
            
            $result = mysqli_query($conn, $sql2);
            $response = array(
                'ok' => true,
            );
            $jsonResponse = json_encode($response);
            header('Content-Type: application/json');
            echo $jsonResponse;  
        } else {
            $response = array(
                'ok' => false,
                'error'=>$result->message
            );
            $jsonResponse = json_encode($response);
            header('Content-Type: application/json');
            echo $jsonResponse; 
        }
    } catch (Exception $error) {
        echo $error->getMessage();
    }

      
}
}
?>