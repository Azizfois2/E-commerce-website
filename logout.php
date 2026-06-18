<?php
require_once 'config.php';

destroyAppSession();

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="0;url=index.php">
    <script>
        localStorage.removeItem('has_active_session');
        localStorage.removeItem('has_active_admin_session');
        localStorage.removeItem('wishlist');
        localStorage.removeItem('cart');
        window.location.replace('index.php');
    </script>
</head>
<body></body>
</html>
