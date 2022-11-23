<?php 
session_start();
$tls_script = "1n1.sh"; # script for the configuration of the tls
$ca_script = "3C4.sh"; # script for the configuration of the ca
$int_ca_script = "1NT_C4.sh"; #script for the configuration of the intermediate ca
$ssh = null; # it will contain the status of the connection
$auth = null; # it will contain the outcome of the authentication command
$stream = null; # each stream corresponds to one CA server type
$errorStream = null;
$int = null;
$tls_errorStream = null; 
$tls_stream = null; 
$ca_errorStream = null; 
$ca_stream= null;
$int_ca_errorStream = null;
$int_ca_stream = null;

try {
    $ssh = ssh2_connect($_SESSION['server_address'], $_SESSION['port_number']);
    $auth = ssh2_auth_password($ssh, $_SESSION['username'], $_SESSION['password']);
} catch (Exception $ex) { echo("connection failed"); }

#checks data provided by the user
function check_input_data() {
    if(str_contains($_POST['hostname'], " ")){
        echo("hostname must not contain spaces");
        return 0;
    }
    if(($_POST['tls_port'] == $_POST['ca_port']) || ($_POST['tls_port'] == $_POST["int_ca_port"]) || ($_POST['ca_port'] == $_POST["int_ca_port"])) {
        echo("the port numbers must be different from each other");
        return 0;
    }
    if(($_POST['tls_port'] > 65535) || ($_POST['ca_port'] > 65535) || ($_POST['int_ca_port'] > 65535) || ($_POST['tls_port'] < 1024) || ($_POST['ca_port'] < 1024) || ($_POST['int_ca_port'] < 1024)){
        echo("the port numbers must be equal to or greater than 1024, or equal to or less than 65535");
        return 0;
    }
    if((str_contains($_POST['tls_admin_username'], " ")) || (str_contains($_POST['tls_admin_password'], " ")) || (str_contains($_POST['ca_admin_username'], " "))
    || (str_contains($_POST['ca_admin_password'], " ")) || (str_contains($_POST['int_ca_admin_username'], " ")) || (str_contains($_POST['int_ca_admin_password'], " "))
    || (str_contains($_POST["tls_name"], " ")) || (str_contains($_POST["ca_name"], " ")) || (str_contains($_POST["int_ca_name"], " "))) {
        echo("usernames, passwords and names must not contain spaces");
        return 0;
    }
    if($_POST['tls_csr_hosts'] != "" && str_starts_with($_POST['tls_csr_hosts'], "*"))
        if(str_starts_with($_POST['tls_csr_hosts'], "*")){
            echo("alternative host name for the TLS CA must start with a letter or a number");
            return 0;
        } 
    else $_POST['tls_csr_hosts'] = "nothing";
    if($_POST['path_length'] < 1 || $_POST['path_length'] > 15) {
        echo("path length must be equal to or greater than 1, or be equal to or less than 15");
        return 0;
    }
    return 1;
}

#this function will send the scripts to the host target
function send_scripts($ssh, $tls_script, $ca_script, $int_ca_script) {
    ssh2_scp_send($ssh, "scripts/1n1.sh", $tls_script, 0767);
    ssh2_scp_send($ssh, "scripts/3C4.sh", $ca_script, 0767);
    ssh2_scp_send($ssh, "scripts/1NT_C4.sh", $int_ca_script, 0767);
}

#modifies the script that configures the TLS CA server
function edit_tls_script($ssh, $tls_script) {
    $stream = ssh2_exec($ssh, "sed -i 's/!TLS_CA_SERVER_PORT/" . $_POST['tls_port'] . "/g' " . $tls_script . "
                        sed -i 's/!TLS_ADMIN_USERNAME/" . $_POST['tls_admin_username'] . "/g' " . $tls_script . "
                        sed -i 's/!TLS_ADMIN_PASSWORD/" . $_POST['tls_admin_password'] . "/g' " . $tls_script . "
                        sed -i 's/!TLS_CA_NAME/" . $_POST['tls_name'] . "/g' " . $tls_script . "
                        sed -i 's/!CA_NAME/" . $_POST['ca_name'] . "/g' " . $tls_script . "
                        sed -i 's/!TLS_CA_SERVER_HOSTS/" . $_POST['tls_csr_hosts'] . "/g' " . $tls_script);
    $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    stream_set_blocking($errorStream, true);
    echo ("<br> <br> <p style='text-align: center; color: darkmagenta'> Output of the tls ca script execution </p>" . stream_get_contents($errorStream));
    fclose($errorStream);
    fclose($stream);
}

#modifies the script that configures the enrollment CA server
function edit_ca_script($ssh, $ca_script) {
    $stream = ssh2_exec($ssh, "sed -i 's/!CA_SERVER_PORT/" . $_POST['ca_port'] . "/g' " . $ca_script . "
                        sed -i 's/!CA_ADMIN_USERNAME/" . $_POST['ca_admin_username'] . "/g' " . $ca_script . "
                        sed -i 's/!CA_ADMIN_PASSWORD/" . $_POST['ca_admin_password'] . "/g' " . $ca_script . "
                        sed -i 's/!CA_NAME/" . $_POST['ca_name'] . "/g' " . $ca_script . "
                        sed -i 's/!TLS_ADMIN_USERNAME/" . $_POST['tls_admin_username'] . "/g' " . $ca_script . "
                        sed -i 's/!TLS_ADMIN_PASSWORD/" . $_POST['tls_admin_password'] . "/g' " . $ca_script . "
                        sed -i 's/!TLS_CA_SERVER_PORT/" . $_POST['tls_port'] . "/g' " . $ca_script . "
                        sed -i 's/!CA_CSR_HOSTS/" . $_POST['ca_csr_hosts'] . "/g' " . $ca_script . "
                        sed -i 's/!PATH_LENGTH/" . $_POST['path_length'] . "/g' " . $ca_script);
    $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    stream_set_blocking($errorStream, true);
    echo ("<br> <br> <p style='text-align: center; color: darkmagenta'> Output of the ca script execution </p>" . stream_get_contents($errorStream));
    fclose($errorStream);
    fclose($stream);
}

#modifies the script that configures the intermediate enrollment CA server
function edit_int_ca_script($ssh, $int_ca_script) {
    $stream = ssh2_exec($ssh, "sed -i 's/!CA_SERVER_PORT/" . $_POST['ca_port'] . "/g' " . $int_ca_script . "
                        sed -i 's/!CA_ADMIN_USERNAME/" . $_POST['ca_admin_username'] . "/g' " . $int_ca_script . "
                        sed -i 's/!CA_ADMIN_PASSWORD/" . $_POST['ca_admin_password'] . "/g' " . $int_ca_script . "
                        sed -i 's/!CA_NAME/" . $_POST['ca_name'] . "/g' " . $int_ca_script . "
                        sed -i 's/!TLS_CA_SERVER_PORT/" . $_POST['tls_port'] . "/g' " . $int_ca_script . "
                        sed -i 's/!TLS_ADMIN_USERNAME/" . $_POST['tls_admin_username'] . "/g' " . $int_ca_script . "
                        sed -i 's/!INT_CA_CSR_HOSTS/" . $_POST['int_ca_csr_hosts'] . "/g' " . $int_ca_script . "
                        sed -i 's/!PATH_LENGTH/" . ($_POST['path_length'] - 1) . "/g' " . $int_ca_script . "
                        sed -i 's/!INT_CA_ADMIN_USERNAME/" . $_POST['int_ca_admin_username'] . "/g' " . $int_ca_script . "
                        sed -i 's/!INT_CA_ADMIN_PASSWORD/" . $_POST['int_ca_admin_password'] . "/g' " . $int_ca_script . "
                        sed -i 's/!INT_CA_SERVER_PORT/" . $_POST['int_ca_port'] . "/g' " . $int_ca_script . "
                        sed -i 's/!INT_CA_NAME/" . $_POST['int_ca_name'] . "/g' " . $int_ca_script . "
                        sed -i 's/!INT_CA_NAME/" . $_POST['int_ca_name'] . "/g' " . $int_ca_script . "
                        sed -i 's/!HOSTNAME/" . $_POST['hostname'] . "/g' " . $int_ca_script);
    $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    stream_set_blocking($errorStream, true);
    echo ("<br> <br> <p style='text-align: center; color: darkmagenta'> Output of the int ca script execution </p>" . stream_get_contents($errorStream));
    fclose($errorStream);
    fclose($stream);
}

#starts the script for configuring TLS CA server
function tls_initialize($ssh, $tls_script) {
    $stream = ssh2_exec($ssh, './' . $tls_script);
    $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    stream_set_blocking($errorStream, true);
    echo ("<br> <br> Output of tls server intialization: " . stream_get_contents($errorStream));
    fclose($errorStream);
    fclose($stream);
}

#starts the script for configuring the root enrollment CA server
function ca_initialize($ssh, $ca_script) {
    if(!($stream = ssh2_exec($ssh, './' . $ca_script))){
        echo("error over the starting of the server");
        exit();
    }
    $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    stream_set_blocking($errorStream, true);
    echo("<br> <br> Output of the ca server initialization: " . stream_get_contents($errorStream));
    fclose($errorStream);
    fclose($stream);
}

#starts the script for configuring the intermediate enrollment CA server
function int_ca_initialize($ssh, $int_ca_script) {
    if(!($stream = ssh2_exec($ssh, './' . $int_ca_script))){
        echo("error over the starting of the server");
        exit();
    }
    $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    stream_set_blocking($errorStream, true);
    echo("<br> <br> Output of the intermediate ca intialization: " . stream_get_contents($errorStream));
    fclose($errorStream);
    fclose($stream);
}

#starts the tls ca server
function tls_start($ssh, &$tls_stream, &$tls_errorStream) {
    $tls_stream = ssh2_exec($ssh, "cd Hyperledger_Fabric_Network/Fabric/fabric-ca-server-tls/
                            nohup ./fabric-ca-server start &");
    $tls_errorStream = ssh2_fetch_stream($tls_stream, SSH2_STREAM_STDERR);
    stream_set_timeout($tls_errorStream, 50);
    sleep(5);
    echo("<br> <br> Output of the tls server start: " . stream_get_contents($tls_errorStream));
}

#start the CA server
function ca_start($ssh, &$ca_stream, &$ca_errorStream) {
    $ca_stream = ssh2_exec($ssh, "cd Hyperledger_Fabric_Network/Fabric/fabric-ca-server-" . $_POST['ca_name'] . "/
                            nohup ./fabric-ca-server start &");
    $ca_errorStream = ssh2_fetch_stream($ca_stream, SSH2_STREAM_STDERR);
    stream_set_timeout($ca_errorStream, 50);
    sleep(5);
    echo("<br> <br> Output of the ca server start: " . stream_get_contents($ca_errorStream));
}

#start the intermediate CA server
function int_ca_start($ssh, &$int_ca_stream, &$int_ca_errorStream) {
    $int_ca_stream = ssh2_exec($ssh, "cd Hyperledger_Fabric_Network/Fabric/fabric-ca-server-" . $_POST['int_ca_name'] . "/
                                nohup ./fabric-ca-server start &");
    $int_ca_errorStream = ssh2_fetch_stream($int_ca_stream, SSH2_STREAM_STDERR);
    stream_set_timeout($int_ca_errorStream, 50);
    sleep(5);
    echo("<br> <br> Output of the int ca server start: " . stream_get_contents($int_ca_errorStream));
}

#enroll the intermediate CA admin
function enroll_int_ca_admin($ssh, &$stream, &$errorStream) {
    $stream = ssh2_exec($ssh, 'cd Hyperledger_Fabric_Network/Fabric/fabric-ca-client/
                                export FABRIC_CA_CLIENT_HOME=$PWD
                                ./fabric-ca-client enroll -d -u https://' . $_POST["int_ca_admin_username"] . ':' . $_POST["int_ca_admin_password"] . '@' . $_POST["hostname"] . ':' . $_POST["int_ca_port"] .  ' --tls.certfiles tls-root-cert/tls-ca-cert.pem --csr.hosts "' . $_POST["int_ca_csr_hosts"] . '" --mspdir ' . $_POST["ca_name"] . '/' . $_POST["int_ca_admin_username"] . '/msp');
    $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
    stream_set_blocking($errorStream, true);
    echo("<br> <br> Output of the int ca admin enrollment: " . stream_get_contents($errorStream)); 
}

#close all the streams
function close_streams(&$tls_errorStream, &$tls_stream, &$ca_errorStream, &$ca_stream, &$errorStream, &$stream, &$int_ca_errorStream, &$int_ca_stream) {
    fclose($errorStream);
    fclose($stream);
    fclose($tls_errorStream);
    fclose($tls_stream);
    fclose($ca_errorStream);
    fclose($ca_stream);
    fclose($int_ca_errorStream);
    fclose($int_ca_stream);
}

?>

<!DOCTYPE html>

<head>
    <title> Outcomes </title>
    <link rel="stylesheet" type="text/css" href="css/welcome_style.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
</head>

<body style="background: lightgray;">
    <h1> Hyperledger Fabric: graphic user interface </h1>
    <p> Tool for configuring and initializing Hyperledger Fabric network </p>
    <br>
    <hr>
    <br>
    <p> Outcomes </p>
    <?php 
    if(!$ssh || !$auth){
        echo("something went wrong during the connection");
        exit();
    } 
    #if some parameter is wrong, the initialization process will be stuck
    if(check_input_data()) {
        send_scripts($ssh, $tls_script, $ca_script, $int_ca_script);
        #TLS CA configuration
        edit_tls_script($ssh, $tls_script);
        tls_initialize($ssh, $tls_script);
        tls_start($ssh, $tls_stream, $tls_errorStream);
        #CA configuration
        edit_ca_script($ssh, $ca_script);
        ca_initialize($ssh, $ca_script);
        ca_start($ssh, $ca_stream, $ca_errorStream);
        #intermediate CA configuration
        edit_int_ca_script($ssh, $int_ca_script);
        int_ca_initialize($ssh, $int_ca_script);
        int_ca_start($ssh, $int_ca_stream, $int_ca_errorStream);
        #enrollment intermediate CA admin
        enroll_int_ca_admin($ssh, $stream, $errorStream);
        close_streams($tls_errorStream, $tls_stream, $ca_errorStream, $ca_stream, $errorStream, $stream, $int_ca_errorStream, $int_ca_stream);
    }
    else echo(" <br> check the parameters and start the intialization process again <br>")
    ?>
    <br>
    <br>
        <form action="/" method="GET" style="text-align: center;">
            <button type="submit"> Go home </button>
        </form>
</body> 