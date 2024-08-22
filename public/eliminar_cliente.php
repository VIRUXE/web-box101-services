<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if (!$logged_user->isAdmin()) header('Location: index.php');

if (!isset($_GET['id'])) header('Location: clientes.php');

include '../database.php';

$id = $db->real_escape_string($_GET['id']);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$db->query("DELETE FROM users WHERE id = $id;");

	echo $db->affected_rows ?
		'<div class="notification is-success">Cliente eliminado com sucesso.</div>' :
		'<div class="notification is-danger">Erro ao eliminar cliente.</div>';
} else { // Ask the user for confirmation
	$result = $db->query("SELECT * FROM users WHERE id = $id;");

	if ($result->num_rows) {
		$user = new User($result->fetch_assoc());

		if ($user->level != UserLevel::Admin && $user->level === UserLevel::Admin) {
			echo '<div class="notification is-danger">Não é possível eliminar um administrador.</div>';
		} else {
			echo <<<HTML
			<h1 class="title">Eliminar Cliente</h1>
			<div class="box">
				<p>Tem a certeza que deseja eliminar o cliente <a href="cliente.php?id={$user->id}">$user</a>?</p>
				<form method="post">
					<div class="field is-grouped">
						<div class="control">
							<button class="button is-danger" type="submit">Eliminar</button>
						</div>
						<div class="control">
							<a class="button is-info" href="clientes.php">Cancelar</a>
						</div>
					</div>
				</form>
			</div>
			HTML;
		}
	} else {
		echo '<div class="notification is-danger">Cliente não encontrado.</div>';
	}
}

echo <<<HTML
	</div>
</section>
HTML;

include 'footer.php';
?>