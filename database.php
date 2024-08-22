<?php
$db = new mysqli('localhost', 'root', '', 'box101');

if ($db->connect_error) die('Impossível estabelecer ligação à base de dados.\n\n' + $db->connect_error);