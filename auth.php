<?php
// Verifica sessão iniciada em config.php
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>