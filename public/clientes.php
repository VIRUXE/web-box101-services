<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

include '../database.php';
include 'header.php';

$search = isset($_GET['pesquisa']) ? $db->real_escape_string($_GET['pesquisa']) : NULL;

$query = $db->query("SELECT * FROM users" . (
	$search ? " WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%'" :
	"") . ';');

echo <<<HTML
<section class="section">
	<div class="container">
HTML;

$user_count = $query->num_rows;

if ($search) {
	if (!$user_count)
		echo '<div class="notification is-warning">Não foram encontrados clientes com o termo de pesquisa "<strong>'.$search.'</strong>".</div>';
	else {
		echo '<div class="notification is-success">' . match ($user_count) {
			1       => 'Foi encontrado <strong>1</strong> cliente".',
			default => 'Foram encontrados <strong>'.$user_count.'</strong> clientes'
		} . ' com o termo de pesquisa "<strong>'.$search.'</strong></div>';
	}
}

echo <<<HTML
		<h1 class="title">Pesquisar por Clientes</h1>
		<form method="get">
			<div class="field has-addons">
				<div class="control is-expanded">
					<input class="input" type="text" name="pesquisa" placeholder="Nome, Email ou Telefone do Cliente" value="$search" minlength="1" required>
				</div>
				<div class="control">
					<button class="button is-info" type="submit">Pesquisar</button>
				</div>
			</div>
		</form>
		<hr>
		<div class="buttons is-grouped">
			<a href="criar_cliente.php" class="button">Criar Cliente</a>
			<a href="" class="button is-text">Voltar</a>
		</div>
		<hr>
		<div class="table-container">
			<table class="table is-fullwidth is-hoverable is-striped is-narrow">
				<thead>
					<tr>
						<th>Nome</th>
						<th>Contacto Móvel</th>
						<th>Email</th>
					</tr>
				</thead>
				<tbody>
HTML;

while ($result = $query->fetch_assoc()) {
	$user = new User($result);

	echo <<<HTML
					<tr>
						<td>
							<a href="cliente.php?id={$user->id}"><strong>{$user}</strong></a> 
HTML;

	if (!$user->isCustomer()) echo match ($user->level) {
		UserLevel::Admin  => '<span class="tag is-danger">Admin</span>',
		UserLevel::Helper => '<span class="tag is-link">Ajudante</span>',
	};

	echo <<<HTML
						</td>
						<td><a href="tel:{$user->getPhoneNumber()}">{$user->getPhoneNumber()}</a></td>
						<td>{$user->renderEmail()}</td>
					</tr>
HTML;
}

echo <<<HTML
				</tbody>
				<tfoot>
					<tr>
						<th colspan="3">Total de Clientes: $user_count</th>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
</section>
HTML;

include 'footer.php';
?>