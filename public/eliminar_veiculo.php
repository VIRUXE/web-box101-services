<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if (!$logged_user->isAdmin()) header('Location: index.php');

if (!isset($_GET['matricula'])) header('Location: veiculos.php');

include '../database.php';

$matricula = $db->real_escape_string($_GET['matricula']);

include 'header.php';

echo <<<HTML
<section class="section">
	<div class="container">
HTML;

$query = $db->query("SELECT * FROM vehicles WHERE matricula = '$matricula';");

if ($query->num_rows) {
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$db->query("DELETE FROM vehicles WHERE matricula = '$matricula';");
		
		if ($db->affected_rows) {
			echo '<div class="notification is-success">Veículo eliminado com sucesso. Será redirecionado para a página de veículos em 5 segundos.</div>';
			header('Refresh: 5; URL=veiculos.php');
		} else
			echo '<div class="notification is-danger">Ocorreu um erro ao eliminar o Veículo com a matrícula <strong>'.$matricula.'</strong>.</div>';
	} else {
		include '../Vehicle.class.php';
		
		$vehicle = new Vehicle($query->fetch_assoc());

		echo <<<HTML
			<h1 class="title">Eliminar Veículo</h1>
			<div class="subtitle">Tem a certeza que deseja eliminar <strong>{$vehicle->brand} {$vehicle->model}</strong> ({$vehicle->getManufactureDate()}, {$vehicle->getColour()}, {$vehicle->getTrim()})?</div>
			<form method="post">
				<div class="field is-grouped">
					<div class="control">
						<button class="button is-danger" type="submit">Eliminar</button>
					</div>
					<div class="control">
						<a class="button is-text" href="veiculos.php">Cancelar</a>
					</div>
				</div>
			</form>
		HTML;
	}
} else
	echo '<div class="notification is-danger">Matrícula não encontrada.</div>';

echo <<<HTML
		</div>
	</div>
</div>
</section>
HTML;

include 'footer.php';