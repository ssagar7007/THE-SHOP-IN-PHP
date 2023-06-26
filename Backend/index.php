<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


$secretValue = $_ENV['JWT_SECRET'];
$gateway = new Braintree\Gateway([
    'environment' => 'sandbox',
    'merchantId' => $_ENV['BRAINTREE_MERCHANT_ID'],
    'publicKey' => $_ENV['BRAINTREE_PUBLIC_KEY'],
    'privateKey' => $_ENV['BRAINTREE_PRIVATE_KEY']
]);

include "connection.php";
include "jwt.php";
include "isAdmin.php";
include "payment.php";
include "getPayload.php";

$requestUrl = $_SERVER['REQUEST_URI'];


//////////////////////////////////////////-----------------Routes---------------------------/////////////////////

/////// Register-- Login---  user-auth --- admin-auth--- forgot-password--- profile--- ///////////////////

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $requestUrl === '/myapp/api/v1/auth/register' ) {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'];
    $email = $data['email'];
    $password = $data['password'];
    $phone = $data['phone'];
    $address = $data['address'];
    $answer = $data['answer'];
    if(!$name || !$email || !$password || !$phone || !$address || !$answer)
    {
        $response = array(
            'success' => false,
            'message' => 'All fields are required',
        );
    }
    else
    {   
        $sql2 = "SELECT email from ecommerce.userdetail where email = '$email'";
        $result = mysqli_query($conn, $sql2);
        if(mysqli_num_rows($result) === 0)
        {
            $sql = "INSERT INTO ecommerce.userdetail (name, email, password, phone, address,answer,role) VALUES ('$name','$email','$password','$phone','$address','$answer',0)";
            if (mysqli_query($conn, $sql)) {
               
                $response = array(
                    'success' => true,
                    'message' => 'User Register Successfully',
        
                );
        
            } else {
             
                $response = array(
                    'success' => false,
                    'message' => 'Error in registration'.mysqli_error($conn),
                
                );
            }
        }
        else
        {
            $response = array(
                'success' => false,
                'message' => 'Email already registered',
            
            ); 
        }
       
    }


    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');
    echo $jsonResponse;
    
}

else if ($_SERVER['REQUEST_METHOD'] === 'POST' && $requestUrl === '/myapp/api/v1/auth/login' ) {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'];
    $password = $data['password'];
    $sql2 = "SELECT * from ecommerce.userdetail where email = '$email' && password = '$password'";
    $result = mysqli_query($conn, $sql2);
    if(mysqli_num_rows($result) > 0)
    {
        while($row = mysqli_fetch_assoc($result)) {
            $user = array(
                'name' => $row["name"],
                'email' => $row["email"],
                'phone' => $row["phone"],
                'address' => $row["address"],
                'role' => $row["role"],
            );
        }
            
        $headers = array('alg'=>'HS256','typ'=>'JWT');
        $payload = array('name'=>$user["name"],'email'=>$user["email"], 'role'=> $user["role"], 'exp'=>(time() + 86400));    // 86400 seconds expiration
    
        $token = generate_jwt($headers, $payload,$secretValue);
    
        // echo $token;
    
        $response = array(
            'success' => true,
            'message' => 'Login Successfully',
            'user'=>$user,
            'token'=>$token
        );
    }
    else
    {
        $response = array(
            'success' => false,
            'message' => 'Invalid Email or Password',
        );
    }

    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');

    
    echo $jsonResponse;
    
}

else if ($_SERVER['REQUEST_METHOD'] === 'GET' && $requestUrl === '/myapp/api/v1/auth/user-auth') {
    $headers = getallheaders();
    $is_jwt_valid = is_jwt_valid($headers['Authorization'],$secretValue);
     if($is_jwt_valid === TRUE) {
         $response = array(
             'ok' => true,
         );
     } else {
         $response = array(
             'ok' => false,
         );
     }

 $jsonResponse = json_encode($response);
 header('Content-Type: application/json');

 
 echo $jsonResponse;
 
}

else if ($_SERVER['REQUEST_METHOD'] === 'GET' && $requestUrl === '/myapp/api/v1/auth/admin-auth') {
         $decoded_payload = getPayload($secretValue);
         if ($decoded_payload !== null) {
             $email = $decoded_payload['email'];
             if(isAdmin($email,$conn)){
                 $response = array(
                     'ok' => true,  
                 );
             }
             else
             {
                 $response = array(
                     'ok' => false,
                 ); 
             }
         } 
         else 
         {
              $response = array(
                  'ok' => false,
              );
         }
 $jsonResponse = json_encode($response);
 header('Content-Type: application/json');

 echo $jsonResponse;
 
}

else if ($_SERVER['REQUEST_METHOD'] === 'POST' && $requestUrl === '/myapp/api/v1/auth/forgot-password' ) {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'];
    $answer = $data['answer'];
    $newPassword = $data['newPassword'];
    $sql2 = "SELECT * from ecommerce.userdetail where email = '$email' && answer = '$answer'";
    $result = mysqli_query($conn, $sql2);
    if(mysqli_num_rows($result) > 0)
    {
        $sql = "UPDATE ecommerce.userdetail SET password ='$newPassword' WHERE email = '$email'"; 
        $result = mysqli_query($conn, $sql);
        $response = array(
            'success' => true,
            'message' => 'Password reset successfully',
        ); 
    }
    else
    {
        $response = array(
            'success' => false,
            'message' => 'Wrong Email or Answer',
        ); 
    }
   
    
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');

    
    echo $jsonResponse;
    
}

else if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $requestUrl === '/myapp/api/v1/auth/profile' ) {
    $data = json_decode(file_get_contents('php://input'), true);
    $decoded_payload = getPayload($secretValue);
    if($decoded_payload !==null)
    {
       $email = $decoded_payload['email'];
       $sql1 = "SELECT * from ecommerce.userdetail where email = '$email'";
       $result1 = mysqli_query($conn, $sql1);
       while($row1 = mysqli_fetch_assoc($result1)) {
           $userid = $row1['_id'];
       }

       $email = $data['email'];
       $password = $data['password'];
       $name = $data['name'];
       $phone = $data['phone'];
       $address = $data['address'];
       if(!$name || !$email || !$password || !$phone || !$address)
        {
            $response = array(
                'success' => false,
                'message' => 'All fields are required',
            );
        }
        else
        {
            $sql2 = "SELECT email from ecommerce.userdetail where email = '$email'";
            $result = mysqli_query($conn, $sql2);
            if(mysqli_num_rows($result) === 1)
            {
                $sql = "UPDATE ecommerce.userdetail SET name = '$name' , email = '$email', password = '$password',phone = '$phone',address = '$address' where _id = $userid";
                if (mysqli_query($conn, $sql)) {
                    $sql3 = "Select * from ecommerce.userdetail where _id = '$userid'";
                    $result3 = mysqli_query($conn, $sql3);
                    while($row3 = mysqli_fetch_assoc($result3)) {
                        $temp = array(
                            'name'=>$row3['name'],
                            'address'=>$row3['address'],
                            'phone'=>$row3['phone'],
                            '_id'=>$row3['_id'],
                            'answer'=>$row3['answer'],
                            'email'=>$row3['email'],
                            'role'=>$row3['role']
                        );
                    }
                    $updatedUser =  $temp ;
                    $response = array(
                        'success' => true,
                        'message' => 'Profile Updated Successfully',
                        'updatedUser'=>$updatedUser
            
                    );
            
                } else {
                    $response = array(
                        'success' => false,
                        'message' => 'Error in registration'.mysqli_error($conn),
                    
                    );
                }
            }
            else
            {
                $response = array(
                    'success' => false,
                    'message' => 'Email already taken by another user',
                
                ); 
            } 
        }
    }


    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');

    
    echo $jsonResponse;
    
}

/////////////////////////////////////////////////////////////////////////////////////////////////////



//////////-----------Categroy detail routes---////////////////////////////


else if ($_SERVER['REQUEST_METHOD'] === 'GET' && $requestUrl === '/myapp/api/v1/category/get-category') {
    $sql = "SELECT * from ecommerce.category";
    $result = mysqli_query($conn, $sql);
    $category = array();
    if(mysqli_num_rows($result) > 0)
    {
        while($row = mysqli_fetch_assoc($result)) {
            $temp = array(
                '_id' => $row["_id"],
                'name' => $row["name"],
                'slug'=>$row["slug"],
                '__v'=>$row["__v"]
            );
            array_push($category,$temp);
        }
    }
    $response = array(
        'success'=> true,
        'message'=> "All Categories List",
        'category'=>$category,
        
    );
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json'); 
    echo $jsonResponse; 
}

else if ($_SERVER['REQUEST_METHOD'] === 'POST' && $requestUrl === '/myapp/api/v1/category/create-category') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'];
    $sql2 = "SELECT * from ecommerce.category where name = '$name'";
    $result = mysqli_query($conn, $sql2);
    if(mysqli_num_rows($result) === 0)
    {  
        $sql = "INSERT INTO ecommerce.category (name,slug,__v) VALUES ('$name','$name',0)";
       
        if (mysqli_query($conn, $sql))
        { 
        $category = array(
            'name'=>$name
        );
        $response = array(
            'success' => true,
            'message' => 'New Category Created',
            'category' => $category
        ); 
        }
    }
    else
    {
        $response = array(
            'success' => false,
            'message' => 'Category already exists',
        ); 
    }
   
    
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');

    
    echo $jsonResponse;
    
}

else if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('/\/myapp\/api\/v1\/category\/update-category\/(\d+)/', $requestUrl, $matches)) {
    $id = $matches[1];
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'];
    $sql2 = "SELECT * from ecommerce.category where _id = $id";
    
    $result = mysqli_query($conn, $sql2);
    
    if(mysqli_num_rows($result) != 0)
    {  
       
        $sql = "UPDATE ecommerce.category SET name = '$name', slug = '$name' where _id = $id";
        
        $result = mysqli_query($conn, $sql);
        if($result === true){
            $sql2 = "Select * from ecommerce.category";
         
            $result2 = mysqli_query($conn,$sql2);
            while ($row = mysqli_fetch_assoc($result2)) {
                $category = array(
                    '_id'=>$row['_id'],
                    'name'=>$row['name'],
                    'slug'=>$row['slug'],
                    '__v'=>$row['__v'],
                );
            }
            
        }
            
        $response = array(
            'success' => true,
            'message' => 'Category updated',
            'category' => $category
        ); 
    }
    else
    {
        $response = array(
            'success' => false,
            'message' => 'Error occured in updating category',
        ); 
    }
   
    
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');

    
    echo $jsonResponse;
    
}

else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('/\/myapp\/api\/v1\/category\/delete-category\/(\d+)/', $requestUrl, $matches)) {
     
    $id = $matches[1];
    $sql = "DELETE from ecommerce.category where _id = $id ";
    if (mysqli_query($conn, $sql)) {
        $response = array(
            'success' => true,
            'message' => 'Category deleted successfully',
        );
      } else {
        $response = array(
            'success' => false,
            'message' => 'Error occured in cagtegory deletion'.mysqli_error($conn),
        );
      }
   
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json'); 
    echo $jsonResponse;
    
}


////////////////////////////////////////////////////////////////////////////////////////////


////////////////////------PRODUCT DETAIL ROUTES ----/////////////////////////////////////////


else if ($_SERVER['REQUEST_METHOD'] === 'GET' && $requestUrl === '/myapp/api/v1/product/product-count') {
    $response = array(
        '_id' => 123,
        'message' => 'Success--- product-count',
    ); 
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');
    echo $jsonResponse;  
}

else if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('/\/myapp\/api\/v1\/product\/product-list\/(\d+)/', $requestUrl, $matches)) {
    $id = $matches[1];
    $data = json_decode(file_get_contents('php://input'), true);
    $sql3 = "Select * from ecommerce.product"; 
    $result3 = mysqli_query($conn, $sql3);
    $prod = array();
    while ($row = mysqli_fetch_assoc($result3)) {
        $temp = array(
            'name' => $row["name"],
            'description' => $row["description"],
            'category' => $row["category"],
            'price' => $row["price"],
            'quantity' => $row["quantity"],
            '_id' => $row["_id"],
            'photo'=>$row["photo"]          
        );
            array_push($prod, $temp);    
    }
  
    $response = array(
        'success' => true,
        'message' => 'All products',
        'countTotal'=>count($prod),
        'products'=>$prod
    );
    
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json'); 
    echo $jsonResponse; 
}

else if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('/\/myapp\/api\/v1\/product\/product-photo\/(\d+)/', $requestUrl, $matches)) {
    $_id = $matches[1];
    $sql3 = "Select photo from ecommerce.product where _id = '$_id'";
    $result3 = mysqli_query($conn, $sql3);
    
    while ($row = mysqli_fetch_assoc($result3)) {
     $photo=$row["photo"];
     
    }
    header('Content-Type: image/jpeg');
    readfile($photo);
   
    
    
    
}

else if ($_SERVER['REQUEST_METHOD'] === 'GET' &&  preg_match('/\/myapp\/api\/v1\/product\/search\/(\w+)/', $_SERVER['REQUEST_URI'], $matches)) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $parts = explode('/', $requestUri);
    $keyword = end($parts);
    $sql3 = "Select * from ecommerce.product"; 
    $result3 = mysqli_query($conn, $sql3);
    $prod = array();
    while ($row = mysqli_fetch_assoc($result3)) {
        if(strpos(strtolower($row['name']),strtolower($keyword))  || strpos(strtolower($row['description']), strtolower($keyword))  || strpos(strtolower($row['category']), strtolower($keyword)))
        {
            $temp = array(
                'name' => $row["name"],
                'description' => $row["description"],
                'category' => $row["category"],
                'price' => $row["price"],
                'quantity' => $row["quantity"],
                '_id' => $row["_id"],
                'photo'=>$row["photo"]          
            );
            array_push($prod, $temp);
        }
    }
    $jsonResponse = json_encode($prod);
    header('Content-Type: application/json'); 
    echo $jsonResponse; 
}

else if ($_SERVER['REQUEST_METHOD'] === 'GET' && $requestUrl === '/myapp/api/v1/product/get-product') {
    $data = json_decode(file_get_contents('php://input'), true);  
    $sql3 = "Select * from ecommerce.product";   
    $result3 = mysqli_query($conn, $sql3);
    $prod = array();
    while ($row = mysqli_fetch_assoc($result3)) {
        $temp = array(
            'name' => $row["name"],
            'description' => $row["description"],
            'category' => $row["category"],
            'price' => $row["price"],
            'quantity' => $row["quantity"],
            '_id' => $row["_id"],
            'photo'=>$row["photo"],
            'slug'=>$row["_id"]         
        );      
            array_push($prod, $temp);   
    } 
    $response = array(
        'success' => true,
        'message' => 'All products',
        'countTotal'=>count($prod),
        'products'=>$prod
    );   
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');    
    echo $jsonResponse;   
}

else if ($_SERVER['REQUEST_METHOD'] === 'GET' &&  preg_match('/\/myapp\/api\/v1\/product\/get-product\/(\d+)/', $_SERVER['REQUEST_URI'], $matches)) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $parts = explode('/', $requestUri);
    $id= end($parts); 
    $sql3 = "Select * from ecommerce.product where _id = $id";   
    $result3 = mysqli_query($conn, $sql3);
    while ($row = mysqli_fetch_assoc($result3)) {
        $prod = array(
            'name' => $row["name"],
            'description' => $row["description"],
            'category' => array('_id'=>$row["category"]),
            'price' => $row["price"],
            'quantity' => $row["quantity"],
            '_id' => $row["_id"],
            'photo'=>$row["photo"],
            'slug'=>$row["_id"]        
        );         
    } 
    $response = array(
        'success' => true,
        'message' => 'Single product fetched',
        'product'=>$prod
    );   
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');    
    echo $jsonResponse;   
}

else if ($_SERVER['REQUEST_METHOD'] === 'GET' &&  preg_match('/\/myapp\/api\/v1\/product\/related-product\/(\d+)/', $_SERVER['REQUEST_URI'], $matches)) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $parts = explode('/', $requestUri);
    $similarcategory = end($parts); 
    $sql3 = "Select * from ecommerce.product where category = $similarcategory";   
    $result3 = mysqli_query($conn, $sql3);
    $prod = array();
    while ($row = mysqli_fetch_assoc($result3)) {
        $temp = array(
            'name' => $row["name"],
            'description' => $row["description"],
            'category' => $row["category"],
            'price' => $row["price"],
            'quantity' => $row["quantity"],
            '_id' => $row["_id"],
            'photo'=>$row["photo"]         
        );  
        array_push($prod,$temp);       
    } 
    $response = array(
        'success' => true,
        'products'=>$prod
    );   
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');    
    echo $jsonResponse;   
}

else if ($_SERVER['REQUEST_METHOD'] === 'POST' && $requestUrl === '/myapp/api/v1/product/create-product') {
    $decoded_payload = getPayload($secretValue);
    if ($decoded_payload !== null) {
        $email = $decoded_payload['email'];
        if(!isAdmin($email,$conn)){
            $response = array(
                'success' => false,
                'message' => 'You are not allowed',
            );
            $jsonResponse = json_encode($response);
            header('Content-Type: application/json'); 
           die($jsonResponse) ;
        }
    }
    $data = $_REQUEST; 
    $name = $data['name'];
    $description = $data['description'];
    $price = $data['price'];
    $quantity = $data['quantity'];
    $category = $data['category'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $uploadFile = $uploadDir . basename($_FILES['photo']['name']);
        // Move the uploaded file to the desired directory
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
            echo "Image Pploaded";
        } else {
            echo "Failed to upload image";
        }
    } else {
        echo "No image file uploaded.";
    }

    $photo = $uploadFile;
    $sql2 = "INSERT INTO ecommerce.product (name,description,price,quantity,category,photo,shipping) VALUES ('$name','$description','$price','$quantity','$category','$photo',true)";
    $result = mysqli_query($conn, $sql2);
    $sql3 = "Select * from ecommerce.product where name = '$name'";
    $result3 = mysqli_query($conn, $sql3);
    while ($row = mysqli_fetch_assoc($result3)) {
        $temp = array(
            'name' => $row["name"],
            'description' => $row["description"],
            'category' => $row["category"],
            'price' => $row["price"],
            'quantity' => $row["quantity"],
            '_id' => $row["_id"],
            'photo'=> base64_encode($row['photo'])
        );
           
        
    }
    $products = $temp;
    $response = array(
        'success' => true,
        'message' => 'Product created successfully ',
        'products'=>$products
    );
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json'); 
    echo $jsonResponse;
    
}

else if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('/\/myapp\/api\/v1\/product\/update-product\/(\d+)/', $requestUrl, $matches)) {
    $productid = $matches[1];
    $decoded_payload = getPayload($secretValue);
    if ($decoded_payload !== null) {
        $email = $decoded_payload['email'];
        if(!isAdmin($email,$conn)){
            $response = array(
                'success' => false,
                'message' => 'You are not allowed',
            );
            $jsonResponse = json_encode($response);
            header('Content-Type: application/json'); 
           die($jsonResponse) ;
        }
    }
   
    $data = $_REQUEST; 
    $name = $data['name'];
    $description = $data['description'];
    $price = $data['price'];
    $quantity = $data['quantity'];
    $category = $data['category'];
    $sql1 = "UPDATE ecommerce.product SET name = '$name', description='$description',price = '$price',quantity= '$quantity',category='$category' where _id = $productid";
    $result1 = mysqli_query($conn, $sql1);
    if($result ===  true){
        $sql2 = "Select * from ecommerce.product where _id = $productid";
        $result2 = mysqli_query($conn, $sql2);
        while ($row = mysqli_fetch_assoc($result3)) {
            $prod = array(
            'name' => $row["name"],
            'description' => $row["description"],
            'category' => $row["category"],
            'price' => $row["price"],
            'quantity' => $row["quantity"],
            '_id' => $row["_id"],
            'photo'=> base64_encode($row['photo'])
        
            );
        }
        $response = array(
            'success'=>true,
            'message' => 'Product updated successfully',
            'products'=>$prod
        );
    }
    else
    {
        $response = array(
            'success'=>false,
            'message' => 'Some  error occured in updating',
        ); 
    }
    
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');
    echo $jsonResponse; 
}

else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && preg_match('/\/myapp\/api\/v1\/product\/delete-product\/(\d+)/', $requestUrl, $matches)) {
    $productid = $matches[1];
    $response = array(
        'id' => 123,
        'message' => 'Success--- delete product   ---'.$productid,
    );
    
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');

    
    echo $jsonResponse;
    
}

else if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('/\/myapp\/api\/v1\/product\/product-category\/(\d+)/', $_SERVER['REQUEST_URI'], $matches)) {
    $id = $matches[1];
    $data = json_decode(file_get_contents('php://input'), true);

    $sql3 = "Select * from ecommerce.product where category = $id"; 
    $result3 = mysqli_query($conn, $sql3);
    $prod = array();
    while ($row = mysqli_fetch_assoc($result3)) {
        $temp = array(
            'name' => $row["name"],
            'description' => $row["description"],
            'category' => $row["category"],
            'price' => $row["price"],
            'quantity' => $row["quantity"],
            '_id' => $row["_id"],
            'photo'=>$row["photo"]          
        );
            array_push($prod, $temp);    
    }
  
    $response = array(
        'success' => true,
        'message' => 'All products',
        'countTotal'=>count($prod),
        'products'=>$prod
    );
    
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json'); 
    echo $jsonResponse; 
}

///////////////////////////////////////////////////////////////////////////////////////////////


/////////---------------ORDER Routes-------------------------////////////////////////////

else if ($_SERVER['REQUEST_METHOD'] === 'GET' && $requestUrl === '/myapp/api/v1/auth/orders' ) {
    // $data = json_decode(file_get_contents('php://input'), true);
    $headers = getallheaders();
    $jwt = $headers['Authorization'];
    $is_jwt_valid = is_jwt_valid($jwt,$secretValue);
    if($is_jwt_valid === TRUE){
        $decoded_payload = decode_jwt($jwt,$secretValue);
        if ($decoded_payload !== null) {
            $email = $decoded_payload['email'];
            $sql3 = "Select * from ecommerce.orderdetail where buyer = '$email'";
            $result3 = mysqli_query($conn, $sql3);
            $response = array();
            while ($row = mysqli_fetch_assoc($result3)) {
                
                $temp = array(
                    '_id' => $row["_id"],
                    'products' => json_decode($row["products"]),    
                    'payment' => array('success'=>true,'transaction'=>$row["payment"]),    
                    'buyer' => $row["buyer"],    
                    'status' => $row["status"],    
                );  
                array_push($response,$temp);          
            }
           
            $jsonResponse = json_encode($response);
            header('Content-Type: application/json');
            echo $jsonResponse;
         }
         else{
            $respnse = array(
                'message'=>"some error occured",    
            );  
            $jsonResponse = json_encode($response);
            header('Content-Type: application/json');
            echo $jsonResponse;
         }
    }

    
   
    
}

else if ($_SERVER['REQUEST_METHOD'] === 'GET' && $requestUrl === '/myapp/api/v1/auth/all-orders' ) {
  
    $sql3 = "Select * from ecommerce.orderdetail";
    $result3 = mysqli_query($conn, $sql3);
   
    $response = array();
    while ($row = mysqli_fetch_assoc($result3)) {
        $temp = array(
            '_id' => $row["_id"],
            'products' => json_decode($row["products"]),    
            'payment' => array('success'=>true,'transaction'=>$row["payment"]),    
            'buyer' => $row["buyer"],    
            'status' => $row["status"],    
        );  
        array_push($response,$temp); 
             
    }
   
    
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');

    
    echo $jsonResponse;
    
}

else if ($_SERVER['REQUEST_METHOD'] === 'PUT' && preg_match('/\/myapp\/api\/v1\/auth\/order-status\/(\d+)/', $requestUrl, $matches)) {
    $data = json_decode(file_get_contents('php://input'), true);
    $status = $data['status'];
    $orderid = $matches[1];
    $sql3 = "UPDATE ecommerce.orderdetail SET status = '$status' where _id = $orderid";
    $result3 = mysqli_query($conn, $sql3);
    if($result3 === true){
            $response = array(
                    '_id' => $orderid,    
                    'status' => $status, 
            );    
    }
    else{
        $response = array(
            'message'=>"Cannot update status some error occured"
    );  
    }
    $jsonResponse = json_encode($response);
    header('Content-Type: application/json');   
    echo $jsonResponse;   
}

///////////////////////////////////////////////////////////////////////////////////////////////










//---------------------------- payment method ////--------------------------------------
///////////////////////////////////////////////////////////////////////////////////////


else if ($_SERVER['REQUEST_METHOD'] === 'GET' && $requestUrl === '/myapp/api/v1/product/braintree/token') { 
    paymentToken($gateway);       
}

else if ($_SERVER['REQUEST_METHOD'] === 'POST' && $requestUrl === '/myapp/api/v1/product/braintree/payment') {
    paymentStatus($gateway,$conn,$secretValue);
}
//////////////////////////////////////////////////////////////////////////////////////

?>