<?php
include_once '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if (!$logged_user->isAdmin()) header('Location: index.php');

include 'header.php';

echo <<<HTML
<section class="section">
	<div class="container">
HTML;
		
if ($_POST) {
}

$matricula = $_GET['matricula'] ?? NULL;
		
if ($matricula) {
	include '../database.php';
	$query = $db->query("SELECT * FROM vehicles WHERE matricula = '$matricula'");

	if ($query->num_rows) {
		include '../Vehicle.class.php';

		$vehicle = new Vehicle($query->fetch_assoc());

		echo <<<HTML
		<h1 class="title">Criar Serviço</h1>
		<p class="subtitle">$vehicle</p>
		<form method="post">
		</form>
		HTML;
	} else echo '<div class="notification is-danger">Veículo não encontrado</div>';
} else echo '<div class="notification is-danger">Matrícula não especificada</div>';

echo <<<HTML
	</div>
</section>
HTML;

include 'footer.php';
?>