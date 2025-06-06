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
    if ($search && strlen($search) >= 2) $where_clause = "WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%' OR nif LIKE '%$search%'";

    $query = $db->query("SELECT id, first_name, last_name, email, phone, nif, address, notes FROM users $where_clause ORDER BY first_name, last_name LIMIT 25");

    if (!$query) throw new Exception($db->error);

    $clients = [];
    while ($client = $query->fetch_assoc()) $clients[] = $client;

    echo json_encode(['clients' => $clients]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro na base de dados: ' . $e->getMessage()]);
}
