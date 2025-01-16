<?php


//Database configuration
$host="localhost";
$username="root";
$password="";
$dbname="registerDB";
$conn="";

//Creating connection
try{
    $conn=new mysqli($host,$username,$password,$dbname);

    //checking connection
    if($conn->connect_error){
        throw new Exception("Database connection failed:".$conn->connect_error);
    }else{
        echo"Database connected successfully! <br>";
        
    }
}catch(Exception $e){
    //catch and display exception
    die("Error:" .$e->getMessage());
}

//retrieviving form data
$FirstName=$_POST['First_name'];
$LastName=$_POST['Last_name'];
$email=$_POST['email'];
$Password=$_POST['password'];

//preparing and executing the sql statement
$sql="INSERT INTO users(FirstName,LastName,Email,`Password`)
VALUES(?,?,?,?)";
$stmt=$conn->prepare($sql);
$stmt->bind_param("ssss",$FirstName,$LastName,$email,$Password);
if($stmt->execute()){
    header("Location: form-login.html");
    exit();
}else{
    echo"Error:" .$stmt->error;
}
$stmt->close();
$conn->close();
?>






