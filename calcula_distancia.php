<?php
require_once 'config.php';
header('Content-Type: application/json');

$orig = isset($_GET['orig']) ? preg_replace('/\D/','',$_GET['orig']) : '';
$dest = isset($_GET['dest']) ? preg_replace('/\D/','',$_GET['dest']) : '';
$valor = isset($_GET['valor']) ? (float)$_GET['valor'] : 0;

function cepCoords($cep){
    $resp = @file_get_contents("https://cep.awesomeapi.com.br/json/{$cep}");
    if(!$resp){
        return null;
    }
    $data = json_decode($resp,true);
    if(isset($data['lat']) && isset($data['lng'])){
        return [(float)$data['lat'],(float)$data['lng']];
    }
    return null;
}

function distanciaKm($c1,$c2){
    if(!$c1 || !$c2) return 0;
    list($lat1,$lon1) = $c1;
    list($lat2,$lon2) = $c2;
    $lat1 = deg2rad($lat1); $lat2 = deg2rad($lat2);
    $lon1 = deg2rad($lon1); $lon2 = deg2rad($lon2);
    $dlat = $lat2 - $lat1; $dlon = $lon2 - $lon1;
    $a = sin($dlat/2)**2 + cos($lat1)*cos($lat2)*sin($dlon/2)**2;
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return 6371 * $c;
}

if(!$orig || !$dest){
    echo json_encode(['distancia'=>null,'valor'=>null]);
    exit;
}
$c1 = cepCoords($orig);
$c2 = cepCoords($dest);
$dist = distanciaKm($c1,$c2);
$valorTotal = $valor * $dist;

echo json_encode(['distancia'=>$dist,'valor'=>$valorTotal]);
