<?php
require_once 'config.php';

destroyAppSession();

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="0;url=index.html">
    <script>
        localStorage.removeItem('has_active_session');
        localStorage.removeItem('cart');
        window.location.replace('index.html');
    </script>
</head>
<body></body>
</html>
