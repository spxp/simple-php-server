<?php

if(PHP_INT_SIZE != 8) {
?>
<html>
  <body>
    <h1>Setup: Precondition failed</h1>
    This server requires PHP compiled with 64bit integers (PHP_INT_SIZE == 8).
  </body>
</html>
<?php
    exit();
}

if(null === SODIUM_LIBRARY_VERSION) {
?>
<html>
  <body>
    <h1>Setup: Precondition failed</h1>
    This server requires the SODIUM extension for PHP.
  </body>
</html>
<?php
    exit();
}

if(file_exists('config.php')) {
?>
<html>
  <body>
    <h1>Setup finished</h1>
    You can now delete this setup.php file.
  </body>
</html>
<?php
    exit();
}

if(isset($_POST['act']) && isset($_POST['baseuri']) && isset($_POST['hostname']) && isset($_POST['port']) &&
   isset($_POST['database']) && isset($_POST['username']) && isset($_POST['password']) && $_POST['act'] == 'setup') {
    $setup_baseuri = $_POST['baseuri'];
    $setup_hostname = $_POST['hostname'];
    $setup_port = $_POST['port'];
    $setup_database = $_POST['database'];
    $setup_username = $_POST['username'];
    $setup_password = $_POST['password'];
    try {
        $pdo = new PDO('mysql:host='.$setup_hostname.';dbname='.$setup_database.';port='.$setup_port.';charset=utf8mb4', $setup_username, $setup_password);
        $createTableDdl =  'CREATE TABLE `roots` (`name` VARCHAR(50) not null, `data` TEXT not null, `published` BIGINT not null, PRIMARY KEY(`name`)) DEFAULT CHARSET=utf8mb4;';
        $createTableDdl .= 'CREATE TABLE `friends` (`name` VARCHAR(50) not null, `data` TEXT not null, `published` BIGINT not null, PRIMARY KEY(`name`)) DEFAULT CHARSET=utf8mb4;';
        $createTableDdl .= 'CREATE TABLE `profiles` (`name` VARCHAR(50) not null, `pk_id` VARCHAR(50), `pk_x` CHAR(44), `state` SMALLINT not null, `requires_connect_token` SMALLINT not null default 0, `registered` BIGINT not null, `setup_token` CHAR(16), PRIMARY KEY(`name`)) DEFAULT CHARSET=utf8mb4;';
        $createTableDdl .= 'CREATE TABLE `posts` (`profile` VARCHAR(50) not null, `seqts` BIGINT not null, `data` TEXT not null, PRIMARY KEY(`profile`, `seqts`), FOREIGN KEY (`profile`) REFERENCES `profiles`(`name`) ON DELETE RESTRICT) DEFAULT CHARSET=utf8mb4;';
        $createTableDdl .= 'CREATE TABLE `devices` (`profile` VARCHAR(50) not null, `device_id` VARCHAR(50) not null, `device_token` CHAR(32) not null, `registered` BIGINT not null, PRIMARY KEY(`profile`, `device_id`), FOREIGN KEY (`profile`) REFERENCES `profiles`(`name`) ON DELETE RESTRICT) DEFAULT CHARSET=utf8mb4;';
        $createTableDdl .= 'CREATE TABLE `profile_keys` (`profile` VARCHAR(50) not null, `grp` VARCHAR(50) not null, `rnd` VARCHAR(10) not null, `enc_with_grp` VARCHAR(50) not null, `enc_with_rnd` VARCHAR(10), `data` TEXT not null, `published` BIGINT not null, PRIMARY KEY(`profile`, `grp`, `rnd`, `enc_with_grp`), FOREIGN KEY (`profile`) REFERENCES `profiles`(`name`) ON DELETE RESTRICT) DEFAULT CHARSET=utf8mb4;';
        $createTableDdl .= 'CREATE TABLE `service_messages` (`profile` VARCHAR(50) not null, `seqts` BIGINT not null, `type` SMALLINT not null, `data` TEXT not null, PRIMARY KEY(`profile`, `seqts`), FOREIGN KEY (`profile`) REFERENCES `profiles`(`name`) ON DELETE RESTRICT) DEFAULT CHARSET=utf8mb4;';
        $createTableDdl .= 'CREATE TABLE `media` (`profile` VARCHAR(50) not null, `media_id` VARCHAR(32) not null, `published` BIGINT not null, PRIMARY KEY(`profile`, `media_id`), FOREIGN KEY (`profile`) REFERENCES `profiles`(`name`) ON DELETE RESTRICT) DEFAULT CHARSET=utf8mb4;';
        $createTableDdl .= 'CREATE TABLE `prepared_connections` (`profile` VARCHAR(50) not null, `establish_id` VARCHAR(50) not null, `expires` BIGINT not null, `published` BIGINT not null, `package` TEXT not null, PRIMARY KEY(`profile`, `establish_id`), FOREIGN KEY (`profile`) REFERENCES `profiles`(`name`) ON DELETE RESTRICT) DEFAULT CHARSET=utf8mb4;';
        $createTableDdl .= 'CREATE TABLE `prepared_keys` (`profile` VARCHAR(50) not null, `establish_id` VARCHAR(50) not null, `grp` VARCHAR(50) not null, `rnd` VARCHAR(10) not null, `enc_with_grp` VARCHAR(50) not null, `enc_with_rnd` VARCHAR(10), `data` TEXT not null, `published` BIGINT not null, PRIMARY KEY(`profile`, `establish_id`, `grp`, `rnd`, `enc_with_grp`), FOREIGN KEY (`profile`) REFERENCES `profiles`(`name`) ON DELETE RESTRICT, FOREIGN KEY (`profile`, `establish_id`) REFERENCES `prepared_connections`(`profile`, `establish_id`) ON DELETE CASCADE) DEFAULT CHARSET=utf8mb4;';
        $createTableDdl .= 'CREATE TABLE `publish` (`profile` varchar(50) NOT NULL, `key_id` varchar(50) NOT NULL, `last` bigint NOT NULL DEFAULT 0, `token` char(16), `scope` varchar(10), PRIMARY KEY(`profile`, `key_id`), FOREIGN KEY (`profile`) REFERENCES `profiles` (`name`) ON DELETE RESTRICT) DEFAULT CHARSET=utf8mb4;';
        $pdo->exec($createTableDdl);
        $jwt_sign_pair = sodium_crypto_sign_keypair();
        $jwt_sign_secret = sodium_crypto_sign_secretkey($jwt_sign_pair);
        $jwt_sign_public = sodium_crypto_sign_publickey($jwt_sign_pair);
        $configFileContent = "<?php\n".
            '$db_hostname = \''.addslashes($setup_hostname)."';\n".
            '$db_port = \''.addslashes($setup_port)."';\n".
            '$db_database = \''.addslashes($setup_database)."';\n".
            '$db_username = \''.addslashes($setup_username)."';\n".
            '$db_password = \''.addslashes($setup_password)."';\n".
            '$jwt_secret = \''.sodium_bin2hex($jwt_sign_secret)."';\n".
            '$jwt_public = \''.sodium_bin2hex($jwt_sign_public)."';\n".
            '$baseUri = \''.addslashes($setup_baseuri)."';\n";
        if(file_put_contents('config.php', $configFileContent)) {
            echo '<html><body><h1>Setup completed</h1></body></html>';
        } else {
            echo '<html><body><h1>Setup failed</h1>Cannot write config.php file</body></html>';
        }
    } catch (PDOException $e) {
?>
<html>
<body>
<h1>Database connection failed</h1>
<?php
    echo '<p>';
    echo $e->getMessage();
    echo '</p>';
    setupForm();
?>
</body>
</html>
<?php
      }
      
} else {
?>
    <html>
    <body>
    <h1>Setup your SPXP Server</h1>
    <?php setupForm(); ?>
    </body>
    </html>
<?php
}

function setupForm() {
?>
<h3>1. Install service info</h3>
Place  this file on your server as <span id='servicefilename'></span>
<pre id='serverinfofile'></pre>
<h3>2. Database setup</h3>
<form method='POST' id='setupform'>
    <input type='hidden' name='act' value='setup'><br/>
    Base URI: <input type='text' name='baseuri' id='baseuri'><br/>
    MySQL Database:<br/>
    Hostname: <input type='text' name='hostname' value='<?php echo htmlspecialchars(isset($_POST['hostname'])?$_POST['hostname']:''); ?>'><br/>
    Port: <input type='text' name='port' value='<?php echo htmlspecialchars(isset($_POST['port'])?$_POST['port']:'3306'); ?>'><br/>
    Database: <input type='text' name='database' value='<?php echo htmlspecialchars(isset($_POST['database'])?$_POST['database']:''); ?>'><br/>
    Username: <input type='text' name='username' value='<?php echo htmlspecialchars(isset($_POST['username'])?$_POST['username']:''); ?>'><br/>
    Password: <input type='text' name='password' value='<?php echo htmlspecialchars(isset($_POST['password'])?$_POST['password']:''); ?>'><br/>
    <button type='submit'>Setup</button>
</form>
<div id='localhosterror' style='display: none;'>You cannot use localhost or 127.0.0.1 as hostname to setup this service</div>
<div id='porterror' style='display: none;'>This server can only run on the default port (80 for http and 443 for https)</div>
<div id='patherror' style='display: none;'>Cannot detect server URL. The path in the address bar does not end with /setup.php</div>
<script type="text/javascript">
window.onload=function(){
    var nonDefaultPort = false;
    if(window.location.port.length > 0) {
        nonDefaultPort =  (window.location.protocol = "http" && window.location.port != "80") || (window.location.protocol = "https" && window.location.port != "443");
    }
    if(window.location.host.toLowerCase() == "localhost" || window.location.host.toLowerCase() == "127.0.0.1") {
        document.getElementById("localhosterror").style.display = "block";
        document.getElementById("setupform").style.display = "none";
    } else if(nonDefaultPort) {
        document.getElementById("porterror").style.display = "block";
        document.getElementById("setupform").style.display = "none";
    } else if(!window.location.pathname.endsWith('/setup.php')) {
        document.getElementById("patherror").style.display = "block";
        document.getElementById("setupform").style.display = "none";
    } else {
        var server_uri = window.location.protocol + "//" + window.location.host;
        var path = window.location.pathname.substring(0, window.location.pathname.length - 10);
        var base_uri = server_uri + path;
        document.getElementById("baseuri").value = base_uri;
        document.getElementById("serverinfofile").innerHTML = "{\n"+
            "    \"start\":\""+base_uri+"/spxp-spe/register\",\n"+
            "    \"bind\":\""+base_uri+"/spxp-spe/bind\",\n"+
            "    \"managementEndpoint\":\""+base_uri+"/spxp-pme\"\n"+
            "}";
            document.getElementById("servicefilename").innerHTML = server_uri + "/.well-known/spxp/spe-discovery";
    }
};
</script>

<?php
}
