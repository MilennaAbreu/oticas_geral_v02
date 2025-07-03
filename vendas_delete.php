<?php
require_once 'config.php';
require_once 'auth.php';

$id = $_GET['id'] ?? null;
if($id){
  $stmt = $pdo->prepare("DELETE FROM VENDAS WHERE ID=?");
  try {
    $stmt->execute([$id]);
  } catch(PDOException $e){
    // ignore for now
  }
}
header('Location: vendas_list.php');
exit;
?>
