<?php
include '../../User.class.php';
include '../../database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

if (!User::isLogged()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Não autorizado']));
}

$search = isset($_GET['pesquisa']) ? $db->real_escape_string($_GET['pesquisa']) : NULL;

try {
    $where_clause = "";
    if ($search && strlen($search) >= 2) $where_clause = "WHERE matricula LIKE '%$search%' OR brand LIKE '%$search%' OR model LIKE '%$search%' OR colour LIKE '%$search%' OR trim LIKE '%$search%' OR notes LIKE '%$search%'";

    $query = $db->query("SELECT matricula, brand, model, colour, trim, notes, registration_date FROM vehicles $where_clause ORDER BY registration_date DESC LIMIT 25");

    if (!$query) throw new Exception($db->error);

    $vehicles = [];
    while ($vehicle = $query->fetch_assoc()) $vehicles[] = $vehicle;

    echo json_encode(['vehicles' => $vehicles]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro na base de dados: ' . $e->getMessage()]);
}
