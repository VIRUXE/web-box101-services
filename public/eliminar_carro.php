<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if (!$logged_user->isAdmin()) header('Location: index.php');

if (!isset($_GET['matricula'])) header('Location: carros.php');

include '../database.php';

$matricula = $db->real_escape_string($_GET['matricula']);

include 'header.php';

echo <<<HTML
<section class="section">
	<div class="container">
HTML;

if ($logged_user->level != UserLevel::Admin) {
	echo <<<HTML
		<div class="notification is-danger">Acesso negado.</div>
	</div>
</section>
HTML;
	include 'footer.php';
	exit;
}

$query = $db->query("SELECT * FROM cars WHERE matricula = '$matricula';");

if (!$query->num_rows) {
	echo <<<HTML
		<div class="notification is-danger">Matrícula não encontrada.</div>
	</div>
</section>
HTML;

	include 'footer.php';
	exit;
}

if ($_POST) {
	echo "<pre>", print_r($_POST), "</pre>";
	$db->query("DELETE FROM cars WHERE matricula = '$matricula';");
	
	echo $db->affected_rows ? 
		'<div class="notification is-success">Carro eliminado com sucesso!</div>' :
		'<div class="notification is-danger">Erro ao eliminar carro!</div>';
} else {
	include '../Car.class.php';
	
	$car = new Car($query->fetch_assoc());

	echo <<<HTML
		<h1 class="title">Eliminar Carro</h1>
		<div class="subtitle">Tem a certeza que deseja eliminar o carro <strong>{$car->brand} {$car->model}</strong> ({$car->getManufactureDate()}, {$car->getColour()}, {$car->getTrim()})?</div>
		<form method="post" action="eliminar_carro.php?matricula=$matricula">
			<button type="submit" class="button is-danger">Eliminar</button>
			<a href="carro.php?matricula=$matricula" class="button is-link">Cancelar</a>
		</form>
		HTML;
}

echo <<<HTML
		</div>
	</div>
</div>
</section>
HTML;

include 'footer.php';