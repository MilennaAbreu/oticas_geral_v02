<?php
require_once 'config.php';
require_once 'auth.php';

unset($_SESSION['venda']);
header('Location: vendas_list.php');
exit;
?>

