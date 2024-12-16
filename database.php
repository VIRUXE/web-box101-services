<?php
try {
    $db = new mysqli('localhost', 'root', '', 'box101');

    if ($db->connect_error) throw new Exception('Impossível estabelecer ligação à base de dados: ' . $db->connect_error);
} catch (Exception $e) {
    die($e->getMessage());
}