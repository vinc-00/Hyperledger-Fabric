<?php
session_start();
if(!$_POST['server_address'] || !$_POST['port_number'] || !$_POST['username'] || !$_POST['password']){
    echo "Error on the parameters provided";
    exit();
}
$ssh = null;
$auth = null;
try {
    $ssh = ssh2_connect($_POST['server_address'], $_POST['port_number']);
    $auth = ssh2_auth_password($ssh, $_POST['username'], $_POST['password']);
} catch (Exception $ex) { }
$_SESSION['server_address'] = $_POST['server_address'];
$_SESSION['port_number'] = $_POST['port_number'];
$_SESSION['username'] = $_POST['username'];
$_SESSION['password'] = $_POST['password'];
?>

<!DOCTYPE html>

<head>
    <title> Main page </title>
    <link rel="stylesheet" type="text/css" href="css/welcome_style.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
</head>

<body>
    <h1> Hyperledger Fabric: graphic user interface </h1>
    <p> Tool for configuring and initializing Hyperledger Fabric network </p>
    <br>
    <hr>
    <?php 
    if (!$ssh || !$auth){ ?>
        <p style='color: red' text-align='center'> ssh connection failed. Please, check out the values typed </p>
        <br>
        <form action="/" method="GET" style="text-align: center;">
            <button type="submit"> Go home </button>
        </form>
        <?php
        exit();
    }
    else{
        echo ("<p style='color: green' text-align='center'> ssh connection was successful </p>"); 
    } 
    ?>
    <p> Please, fill in the following fields, providing the information in order to set up the network </p>

    <form action="/connection" method="POST">
        {{ csrf_field() }}

        <p> Target host </p>
        <input type="text" id="hostname" name="hostname" placeholder="hostname of the target machine" required>
        
        <br>
        <br>

        <p> TLS Certificate Authority </p>
        <input type="text" id="tls_admin_username" name="tls_admin_username" placeholder="Username of the TLS CA admin" required>

        <br>
        <br>

        <input type="password" id="tls_admin_password" name="tls_admin_password" placeholder="Password of the TLS CA admin" required>

        <br>
        <br>

        <input type="number" id="tls_port" name="tls_port" placeholder="Port number of the TLS CA server" min="1024" max="65535" required>

        <br>
        <br>

        <input type="text" id="tls_name" name="tls_name" placeholder="Name of the TLS CA" required>

        <br>
        <br>

        <input type="text" id="tls_csr_hosts" name="tls_csr_hosts" placeholder="Alternative hostname (optional)">

        <br>
        <br>

        <p> Enrollment Certificate Authority </p>
        <input type="text" id="ca_admin_username" name="ca_admin_username" placeholder="Admin username" required>

        <br>
        <br>

        <input type="password" id="ca_admin_password" name="ca_admin_password" placeholder="Admin password" required>

        <br>
        <br>

        <input type="number" id="ca_port" name="ca_port" placeholder="Port number of the enrollment CA" min="1024" max="65535" required>

        <br>
        <br>

        <input type="text" id="ca_name" name="ca_name" placeholder="Name of the enrollment CA" required>
        
        <br>
        <br>

        <input type="text" id="ca_csr_hosts" name="ca_csr_hosts" placeholder="Validity domain of the certificates issued by CA" required>
        
        <br>
        <br>

        <input type="number" id="path_length" name="path_length" placeholder="CA tree height (default 1)" min="1" max="15" required>
        
        <br>
        <br>

        <p> Intermediate Enrollment Certificate Authority </p>
        <input type="text" id="int_ca_admin_username" name="int_ca_admin_username" placeholder="Username of the intermediate CA admin" required>

        <br>
        <br>

        <input type="password" id="int_ca_admin_password" name="int_ca_admin_password" placeholder="Password of the intermediate CA admin" required>

        <br>
        <br>

        <input type="number" id="int_ca_port" name="int_ca_port" placeholder="Port number of the intermediate CA server" min="1024" max="65535" required>

        <br>
        <br>

        <input type="text" id="int_ca_name" name="int_ca_name" placeholder="Name of the intermediate CA" required>

        <br>
        <br>

        <input type="text" id="int_ca_csr_hosts" name="int_ca_csr_hosts" placeholder="Validity domain of the certificates issued by intermediate CA" required>
        
        <br>
        <br>

        <input type="submit" value="start the network">
    </form>
</body>