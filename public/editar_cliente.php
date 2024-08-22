<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if ($logged_user->level !== UserLevel::Admin) header('Location: index.php');

include 'header.php';
include '../database.php';

$id = isset($_GET['id']) ? $db->real_escape_string($_GET['id']) : NULL;

echo <<<HTML
	<section class="section">
		<div class="container">
HTML;

if (!$id) {
	echo <<<HTML
			<div class="notification is-danger">ID do cliente não foi fornecido.</div>
		</div>
	</section>
HTML;
	include 'footer.php';
	exit;
} else {
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$first_name = $db->real_escape_string($_POST['first_name']);
		$last_name  = $db->real_escape_string($_POST['last_name']);
		$email      = $db->real_escape_string($_POST['email']);
		$level      = $db->real_escape_string($_POST['level']);

		$db->query("UPDATE users SET first_name = '$first_name', last_name = '$last_name', email = '$email', level = '$level' WHERE id = $id;");

		if ($db->affected_rows) {
			echo '<div class="notification is-success">Cliente atualizado com sucesso.</div>';
		} else {
			echo '<div class="notification is-danger">Erro ao atualizar cliente.</div>';
		}
	}
}

$query = $db->query("SELECT * FROM users WHERE id = $id;");

if (!$query->num_rows)
	echo '<div class="notification is-danger">Cliente não encontrado.</div>';
else {
	$user = new User($query->fetch_assoc());

	echo <<<HTML
				<h1 class="title">$user</h1>
				<div class="subtitle">{$user->getLevelTitle()}</div>
				<hr>
				<a href="clientes.php" class="button is-link">Voltar</a>
				<hr>
				<form method="post" action="{$_SERVER['REQUEST_URI']}">
					<input type="hidden" name="id" value="$user->id">
					<div class="field">
						<label class="label">Nome</label>
						<div class="control">
							<input class="input" type="text" name="first_name" value="{$user->first_name}" required>
						</div>
					</div>
					<div class="field">
						<label class="label">Apelido</label>
						<div class="control">
							<input class="input" type="text" name="last_name" value="{$user->last_name}" required>
						</div>
					</div>
					<div class="field">
						<label class="label">Email</label>
						<div class="control">
							<input class="input" type="email" name="email" value="{$user->email}" required>
						</div>
					</div>
					<div class="field">
						<label class="label">Nível</label>
						<div class="control">
							<div class="select">
								<select name="level" required>
HTML;

foreach (UserLevel::toArray() as $level) {
	$option = match ($level) {
		UserLevel::Customer => 'Cliente',
		UserLevel::Helper   => 'Ajudante',
		UserLevel::Admin    => 'Administrador',
	};
	echo '<option value="'.$level->value.'"'.($user->level === $level ? ' selected' : '').'>'.$option.'</option>';
}

	echo <<<HTML
								</select>
							</div>
						</div>
					</div>
					<div class="field">
						<div class="control">
							<button class="button is-success" type="submit">Guardar</button>
						</div>
					</div>
				</form>
			</div>
		</section>
	HTML;

}