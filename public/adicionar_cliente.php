<?php
include_once '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if (!$logged_user->isAdmin()) header('Location: index.php');

include 'header.php';

$first_name = '';
$last_name  = '';
$email      = '';
$address    = '';
$phone      = '';
$level      = 'CUSTOMER';

echo <<<HTML
<section class="section">
	<div class="container">
HTML;

if ($_POST) {
	include '../database.php';

	$first_name = $db->real_escape_string($_POST['first_name']);
	$last_name  = $db->real_escape_string($_POST['last_name']);
	$email      = $db->real_escape_string($_POST['email']);
	$address    = $db->real_escape_string($_POST['address']);
	$phone      = $db->real_escape_string($_POST['phone']);
	$level      = $db->real_escape_string($_POST['level']);

	try {
		$db->query("INSERT INTO users (first_name, last_name, email, address, phone, level) VALUES ('$first_name', '$last_name', '$email', '$address', '$phone', '$level');");

		if ($db->affected_rows) {
			echo '<div class="notification is-success">Cliente adicionado com sucesso!</div>';
		} else {
			echo '<div class="notification is-danger">Erro ao adicionar cliente!</div>';
		}
	} catch (mysqli_sql_exception $e) {
		$message = '<div class="notification is-danger">Erro ao adicionar cliente: ';

		switch ($e->getCode()) {
			case 1062:
				$message .= 'Esse email já está registado.';
				break;
			default:
				$message .= $e->getMessage();
				break;
		}

		echo $message.'</div>';
	}
}

echo <<<HTML
		<h1 class="title">Adicionar Cliente</h1>
		<form method="post" action="adicionar_cliente.php">
			<div class="columns is-mobile">
				<div class="column is-half">
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Primeiro Nome</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="text" name="first_name" placeholder="Primeiro Nome" minlength="2" pattern="^[A-Za-zÀ-ÿ\s]{2,}$" title="O nome deve conter apenas letras e pelo menos 2 caracteres." value="$first_name" required>
								</div>
							</div>
						</div>
					</div>
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Último Nome</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="text" name="last_name" placeholder="Último Nome" minlength="2" pattern="^[A-Za-zÀ-ÿ\s]{2,}$" title="O sobrenome deve conter apenas letras e pelo menos 2 caracteres." value="$last_name" required>
								</div>
							</div>
						</div>
					</div>

					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Email</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="email" name="email" placeholder="Email" value="$email" required>
								</div>
							</div>
						</div>
					</div>

					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Endereço</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="text" name="address" placeholder="Endereço" value="$address" required>
								</div>
							</div>
						</div>
					</div>
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Telefone</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="tel" name="phone" placeholder="Telefone" pattern="^\+?[0-9\s-]{7,15}$" title="Insira um número de telefone válido, com 7 a 15 dígitos." value="$phone" required>
								</div>
							</div>
						</div>
					</div>
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Nível</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<div class="select">
										<select name="level">
											<option value="CUSTOMER">Cliente</option>
											<option value="HELP">Ajudante</option>
											<option value="ADMIN">Administrador</option>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- Notes -->
					<div class="field is-horizontal">
						<div class="field-label">
							<label class="label">Notas</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<textarea name="notes" class="textarea" placeholder="Notas sobre como o cliente surgiu, ou o seu caracter." rows="3"></textarea>
								</div>
							</div>
						</div>
					</div>
					
				</div>
			</div>

			<div class="field is-horizontal">
				<div class="field-label">
					<!-- Left empty for spacing -->
				</div>
				<div class="field-body">
					<div class="field">
						<div class="control">
							<button class="button is-success" type="submit">Adicionar Cliente</button>
							<button class="button is-danger" type="reset">Limpar</button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</section>
HTML;

/* 
CREATE TABLE `users` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`email` VARCHAR(254) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`first_name` TINYTEXT NOT NULL COLLATE 'utf8mb4_general_ci',
	`last_name` TINYTEXT NOT NULL COLLATE 'utf8mb4_general_ci',
	`nif` INT(9) UNSIGNED NULL DEFAULT NULL COMMENT 'Número de Identificação Fiscal',
	`address` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_bin',
	`phone` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_general_ci',
	`pin` SMALLINT(4) UNSIGNED ZEROFILL NULL DEFAULT NULL,
	`level` ENUM('CUSTOMER','HELP','ADMIN') NULL DEFAULT 'CUSTOMER' COLLATE 'utf8mb4_general_ci',
	`active` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `email` (`email`) USING BTREE,
	UNIQUE INDEX `nif` (`nif`) USING BTREE
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=3
;

 */
