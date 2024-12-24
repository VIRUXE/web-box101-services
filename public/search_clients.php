<?php
include_once '../User.class.php';

session_start();

if (!User::isLogged()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Not authorized');
}

include '../database.php';

$search = $db->real_escape_string($_GET['q'] ?? '');

if (strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

$query = $db->query("
    SELECT id, first_name, last_name, email, phone 
    FROM users 
    WHERE (first_name LIKE '%$search%' 
        OR last_name LIKE '%$search%' 
        OR email LIKE '%$search%' 
        OR phone LIKE '%$search%')
    LIMIT 10
");

$results = [];
while ($row = $query->fetch_assoc()) {
    $results[] = [
        'id'         => $row['id'],
        'first_name' => $row['first_name'],
        'last_name'  => $row['last_name'],
        'email'      => $row['email'],
        'phone'      => $row['phone']
    ];
}

header('Content-Type: application/json');
echo json_encode($results);