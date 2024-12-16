<?php
/* 
 Display Customer information
 Table Columns: id, email, first name, last name, nif, address, phone, pin, level (CUSTOMER, HELPER, ADMIN), notes, active
 */

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

$query = $db->query("SELECT * FROM users WHERE id = $id;");

if ($query->num_rows) {
	$user = new User($query->fetch_assoc());

	echo <<<HTML
		<h1 class="title">$user</h1>
		<div class="subtitle">{$user->email} <span class="tag">{$user->getLevelTitle()}</span></div>
		<hr>
		<div class="buttons is-grouped">
			<a href="editar_cliente.php?id=$id" class="button is-info">Editar Cliente</a>
			<a href="eliminar_cliente.php?id=$id" class="button is-danger">Eliminar Cliente</a>
			<a href="clientes.php" class="button is-text">Voltar</a>
		</div>
		<hr>
		<h2 class="title">Dados Pessoais</h2>
		<table class="table is-fullwidth">
			<tbody>
				<tr>
					<th>ID</th>
					<td>{$user->id}</td>
				</tr>
				<tr>
					<th>NIF</th>
					<td>{$user->getNif()}</td>
				</tr>
				<tr>
					<th>Morada</th>
					<td>{$user->getAddress()}</td>
				</tr>
				<tr>
					<th>Contacto Móvel</th>
					<td><a href="tel:{$user->getPhoneNumber()}">{$user->getPhoneNumber()}</a></td>
				</tr>
				<tr>
					<th>PIN</th>
					<td>{$user->pin}</td>
				<tr>
					<th>Ativo</th>
					<td>{$user->getActive()}</td>
				</tr>
				<tr>
					<th>Notas</th>
					<td>{$user->getNotes()}</td>
				</tr>
			</tbody>
		</table>
HTML;
} else
	echo '<div class="notification is-danger">Cliente não encontrado.</div>';

echo <<<HTML
	</div>
</section>
HTML;

include 'footer.php';
?>