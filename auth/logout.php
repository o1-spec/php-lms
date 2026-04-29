<?php
session_start();
session_destroy();

header('Location: /library/auth/login.php');
exit();
?>
