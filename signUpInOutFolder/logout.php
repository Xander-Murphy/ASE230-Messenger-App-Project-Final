<?php

session_start();
$_SESSION = [];
session_destroy();
header("Location: ../users/index.php");
exit;

?>