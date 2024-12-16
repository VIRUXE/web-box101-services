<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if (!$logged_user->isAdmin()) header('Location: index.php');

if (!isset($_GET['id'])) header('Location: clientes.php');

include '../database.php';

$id = $db->real_escape_string($_GET['id']);

if (!is_numeric($id)) header('Location: clientes.php');

include 'header.php';

echo <<<HTML
<section class="section">
	<div class="container">
HTML;
		
// Before anything, let's check if a user id was passed and if it's valid
$query = $db->query("SELECT * FROM users WHERE id = $id;");

if ($query->num_rows) { // If the user id was valid
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$db->query("DELETE FROM users WHERE id = $id;");

		if ($db->affected_rows) {
			echo '<div class="notification is-success">Cliente eliminado com sucesso. Será redirecionado para a página de clientes em 5 segundos.</div>';
			header('Refresh: 5; URL=clientes.php');
		} else
			echo '<div class="notification is-danger">Erro ao eliminar cliente.</div>';
	} else { // Ask the user for confirmation
		$user = new User($query->fetch_assoc());

		if ($logged_user->level != UserLevel::Admin && $user->level === UserLevel::Admin) {
			echo '<div class="notification is-danger">Não é possível eliminar um administrador.</div>';
		} else {
			echo <<<HTML
			<h1 class="title">Eliminar Cliente</h1>
			<p class="subtitle">Tem a certeza que deseja eliminar o cliente <a href="cliente.php?id={$user->id}">$user</a>?</p>
			<form method="post">
				<div class="field is-grouped">
					<div class="control">
						<button class="button is-danger" type="submit">Eliminar</button>
					</div>
					<div class="control">
						<a class="button is-text" href="clientes.php">Cancelar</a>
					</div>
				</div>
			</form>
			HTML;
		}
	}
} else
	echo '<div class="notification is-danger">ID de Cliente inválido.</div>';

echo <<<HTML
	</div>
</section>
HTML;

include 'footer.php';
?>