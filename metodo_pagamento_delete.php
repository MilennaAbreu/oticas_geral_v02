<?php
require_once 'config.php';
require_once 'auth.php';

$id = $_GET['id'] ?? null;
$error = false;
if($id){
    try{
        $pdo->prepare("DELETE FROM METODO_PAGAMENTO WHERE id=?")->execute([$id]);
    }catch(PDOException $e){
        if($e->errorInfo[1] == 1451){
            $error = true;
        }else{
            throw $e;
        }
    }
}
$redirect = 'metodo_pagamento_list.php';
if($error) $redirect .= '?erro=1';
header('Location: '.$redirect);
exit();
