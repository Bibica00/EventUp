<?php
session_start();
session_unset();
session_destroy();
header("Location: index.php"); // redireciona para a página inicial
exit();
?>
