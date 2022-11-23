<!DOCTYPE html>
<html>

<head>
    <title> Hyperledger Fabric: main page </title>
    <link rel="stylesheet" type="text/css" href="css/welcome_style.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
</head>

<body>
    <h1> Hyperledger Fabric: graphic user interface </h1>
    <p> Tool for configuring and initializing Hyperledger Fabric network </p>
    <br>
    <hr>
    <p> Please fill the following fields in order to connect to the target host </p>
    <br>
    <br>
    <form action="/main" method="POST">
        {{ csrf_field() }}

        <input type="text" id="server_address" name="server_address" placeholder="Ip address or hostname of the target server" maxlength="40" required>

        <br>
        <br>

        <input type="number" id="port_number" name="port_number" placeholder="Port number" min="1" max="65535" required>

        <br>
        <br>

        <input type="text" id="username" name="username" placeholder="Username" maxlength="30" title="Username of the target host user" required>

        <br>
        <br>

        <input type="password" id="password" name="password" placeholder="Password" maxlength="30" title="Password of the target host user" required>

        <br>
        <br>

        <input type="submit" value="connect to the server">
    </form>
</body>

</html>

<!-- ctrl + shift + i for indentation -->