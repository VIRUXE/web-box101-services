<?php
include '../../User.class.php';
include '../../database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

if (!User::isLogged()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Não autorizado']));
}

try {
    $query = $db->query("SELECT DISTINCT brand FROM vehicles ORDER BY brand ASC");
    
    if (!$query) throw new Exception($db->error);

    $brands = [];
    while ($brand = $query->fetch_assoc()) $brands[] = $brand['brand'];

    echo json_encode(['brands' => $brands]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro na base de dados: ' . $e->getMessage()]);
}
