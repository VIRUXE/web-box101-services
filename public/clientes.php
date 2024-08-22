<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

include '../database.php';
include 'header.php';

const BASE_QUERY = "SELECT * FROM users";

$search = isset($_GET['pesquisa']) ? $db->real_escape_string($_GET['pesquisa']) : NULL;

$query = $db->query(BASE_QUERY . ($search ? " WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%'" : "") . ';');

echo <<<HTML
<section class="section">
	<div class="container">
HTML;

$count = $query->num_rows;

if ($search) {
	if (!$count)
		echo '<div class="notification is-warning">Não foram encontrados clientes com o termo de pesquisa "<strong>'.$search.'</strong>".</div>';
	else {
		$message = '<div class="notification is-success">';
		
		if ($count === 1)
			$message .= 'Foi encontrado <strong>1</strong> cliente com o termo de pesquisa "<strong>'.$search.'</strong>".';
		else
			$message .= 'Foram encontrados <strong>'.$count.'</strong> clientes com o termo de pesquisa "<strong>'.$search.'</strong>".';

		echo $message.'</div>';
	}
}

echo <<<HTML
		<h1 class="title">Pesquisar por Clientes</h1>
		<form method="get">
			<div class="field has-addons">
				<div class="control is-expanded">
					<input class="input" type="text" name="pesquisa" placeholder="Nome, Email ou Telefone do Cliente" value="$search" minlength="2" required>
				</div>
				<div class="control">
					<button class="button is-info" type="submit">Pesquisar</button>
				</div>
			</div>
		</form>
		<hr>
		<a href="adicionar_cliente.php" class="button is-success">Adicionar Cliente</a>
		<a href="index.php" class="button is-info">Voltar</a>
		<hr>
		<table class="table is-fullwidth is-hoverable">
			<thead>
				<tr>
					<th>Nome</th>
					<th>Email</th>
					<th>Telefone</th>
					<th>Endereço</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
HTML;

while ($result = $query->fetch_assoc()) {
	$user = new User($result);

	echo <<<HTML
				<tr>
					<td>
						<strong>{$user}</strong> 
HTML;

	echo match ($user->level) {
		UserLevel::Admin => '<span class="tag is-danger">Admin</span>',
		UserLevel::Helper => '<span class="tag is-link">Ajudante</span>',
	};

	echo <<<HTML
					</td>
					<td><a href="mailto:{$user->getEmail()}">{$user->getEmail()}</a></td>
					<td><a href="tel:{$user->getPhoneNumber()}">{$user->getPhoneNumber()}</a></td>
					<td>{$user->getAddress()}</td>
					<td>
						<a href="cliente.php?id={$user->id}" class="button is-link">Ver</a>
						<a href="editar_cliente.php?id={$user->id}" class="button is-info">Editar</a>
						<a href="eliminar_cliente.php?id={$user->id}" class="button is-danger">Eliminar</a>
					</td>
				</tr>
HTML;
}

echo <<<HTML
			</tbody>
		</table>
	</div>
</section>
HTML;

include 'footer.php';
?>