<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if (!$logged_user->isAdmin()) header('Location: index.php');

if (!isset($_GET['id'])) header('Location: clientes.php');

$id = $_GET['id'] ?? NULL;

if (!is_numeric($id)) header('Location: clientes.php');

include 'header.php';
include '../database.php';

echo <<<HTML
	<section class="section">
		<div class="container">
HTML;

$id = $db->real_escape_string($_GET['id']);
$query = $db->query("SELECT * FROM users WHERE id = $id;");

if ($query->num_rows) {
	if ($_POST) {
		$email      = !empty($_POST['email']) ? $_POST['email'] : NULL;
		$first_name = $_POST['first_name'];
		$last_name  = !empty($_POST['last_name']) ? $_POST['last_name'] : NULL;
		$nif        = !empty($_POST['nif']) ? $_POST['nif'] : NULL;
		$address    = !empty($_POST['address']) ? $_POST['address'] : NULL;
		$phone      = $_POST['phone'];
		$pin        = $_POST['pin'];
		$level      = !empty($_POST['level']) ? $_POST['level'] : NULL;
		$notes      = !empty($_POST['notes']) ? $_POST['notes'] : NULL;

		try {
			$stmt = $db->prepare("UPDATE users SET email = ?, first_name = ?, last_name = ?, nif = ?, address = ?, phone = ?, pin = ?, level = ?, notes = ? WHERE id = ?");
			$stmt->bind_param("sssisssssi", $email, $first_name, $last_name, $nif, $address, $phone, $pin, $level, $notes, $id);
			$stmt->execute();

			if ($db->affected_rows)
				header('Location: cliente.php?id='.$id);
			else
				echo '<div class="notification is-warning">Não foram feitas alterações.</div>';
		} catch (mysqli_sql_exception $e) {
			echo '<div class="notification is-danger">Erro ao atualizar cliente: ' . match ($e->getCode()) {
				1062    => 'Esse email já está registado.',
				default => $e->getMessage() . " ({$e->getCode()})",
			} . '</div>';
		}
	}

	$user = new User($query->fetch_assoc());

	echo <<<HTML
				<h1 class="title">Editar <a href="cliente.php?id=$id">$user</a></h1>
				<div class="subtitle">{$user->getLevelTitle()}</div>
				<hr>
				<form method="post">
					<div class="columns">
						<div class="column is-7">
							<!-- Email -->
							<div class="field is-horizontal">
								<div class="field-label is-normal">
									<label class="label">Email</label>
								</div>
								<div class="field-body">
									<div class="field">
										<div class="control">
											<input class="input" type="email" name="email" value="{$user->email}">
										</div>
									</div>
								</div>
							</div>
							<!-- first_name -->
							<div class="field is-horizontal">
								<div class="field-label is-normal">
									<label class="label">Nome</label>
								</div>
								<div class="field-body">
									<div class="field">
										<div class="control">
											<input class="input" type="text" name="first_name" value="{$user->first_name}" required>
										</div>
									</div>
								</div>
							</div>
							<!-- last_name -->
							<div class="field is-horizontal">
								<div class="field-label is-normal">
									<label class="label">Apelido</label>
								</div>
								<div class="field-body">
									<div class="field">
										<div class="control">
											<input class="input" type="text" name="last_name" value="{$user->last_name}" required>
										</div>
									</div>
								</div>
							</div>
							<!-- nif -->
							<div class="field is-horizontal">
								<div class="field-label is-normal">
									<label class="label">NIF</label>
								</div>
								<div class="field-body">
									<div class="field">
										<div class="control">
											<input class="input" type="text" name="nif" value="{$user->nif}">
										</div>
									</div>
								</div>
							</div>
							<!-- address -->
							<div class="field is-horizontal">
								<div class="field-label is-normal">
									<label class="label">Morada</label>
								</div>
								<div class="field-body">
									<div class="field">
										<div class="control">
											<input class="input" type="text" name="address" value="{$user->address}">
										</div>
									</div>
								</div>
							</div>
							<!-- Phone -->
							<div class="field is-horizontal">
								<div class="field-label is-normal">
									<label class="label">Contacto Móvel</label>
								</div>
								<div class="field-body">
									<div class="field">
										<div class="control">
											<input class="input" type="tel" name="phone" value="{$user->phone}" required>
										</div>
									</div>
								</div>
							</div>
							<!-- pin -->
							<div class="field is-horizontal">
								<div class="field-label is-normal">
									<label class="label">PIN</label>
								</div>
								<div class="field-body">
									<div class="field">
										<div class="control">
											<input class="input" type="number" name="pin" value="{$user->pin}" maxlength="4" min="0" max="9999" step="1" required>
										</div>
									</div>
								</div>
							</div>
							<div class="field is-horizontal">
								<div class="field-label">
									<label class="label">Nível</label>
								</div>
								<div class="field-body">
									<div class="field">
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
								</div>
							</div>
							<!-- Notes -->
							<div class="field is-horizontal">
								<label class="field-label">
									<label class="label">Notas</label>
								</label>
								<div class="field-body">
									<div class="field">
										<div class="control">
											<textarea class="textarea" name="notes">{$user->notes}</textarea>
										</div>
									</div>
								</div>
							</div>
							<div class="field">
								<div class="buttons is-grouped">
									<div class="control">
										<button class="button is-success" type="submit">Guardar</button>
									</div>
									<div class="control">
										<a href="clientes.php" class="button is-text">Voltar</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</section>
	HTML;
}