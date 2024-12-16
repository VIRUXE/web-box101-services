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

	// Replaces characters that should have tildes or accents in Portuguese, with the correct ones
	function formatToPortuguese($string) {
		return strtr($string, [
			'ao'      => 'ão',
			'Antonio' => 'António', 
		]);
	}

	try {
		$email      = !empty($_POST['email']) ? $_POST['email'] : NULL;
		$first_name = formatToPortuguese($_POST['first_name']);
		$last_name  = !empty($_POST['last_name']) ? formatToPortuguese($_POST['last_name']) : NULL;
		$nif        = !empty($_POST['nif']) ? $_POST['nif'] : NULL;
		$address    = !empty($_POST['address']) ? $_POST['address'] : NULL;
		$phone      = $_POST['phone'];
		$pin        = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
		$level      = !empty($_POST['level']) ? $_POST['level'] : NULL;
		$notes      = !empty($_POST['notes']) ? $_POST['notes'] : NULL;

		$stmt = $db->prepare("INSERT INTO users (email, first_name, last_name, nif, address, phone, pin, level, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);");
		$stmt->bind_param('sssississ', $email, $first_name, $last_name, $nif, $address, $phone, $pin, $level, $notes);
		$stmt->execute();

		if ($db->affected_rows) {
			$user_name = $last_name ? $first_name . ' ' . $last_name : $first_name;
			$user_name = '<a href="cliente.php?id='.$db->insert_id.'"><strong>'.$user_name.'</strong></a>';
			echo '<div class="notification is-success">Cliente '.$user_name.' adicionado com sucesso! PIN gerado: '.$pin.'</div>';
			
			// Reset form values, so the user can add another client
			$email = ''; $first_name = ''; $last_name = ''; $nif = ''; $address = ''; $phone = ''; $level = ''; $notes = ''; 
		} else
			'<div class="notification is-danger">Erro ao adicionar cliente!</div>'; 
	} catch (mysqli_sql_exception $e) {
		// I don't know how I feel about this.
		echo '<div class="notification is-danger">Erro ao adicionar Cliente: <strong>' . match($e->getCode()) {
			1062    => 'Esse email já existe.',
			default => $e->getMessage(),
		} . '</strong></div>';
	}
}

echo <<<HTML
		<h1 class="title">Criar Cliente</h1>
		<form method="post" action="criar_cliente.php" autocomplete="off">
			<div class="columns">
				<div class="column is-5">
					<!-- email -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Email</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="email" name="email" placeholder="Email" value="$email" pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" title="Insira um email válido." autocomplete="off">
								</div>
							</div>
						</div>
					</div>
					<!-- first_name -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Primeiro Nome</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="text" name="first_name" placeholder="Primeiro Nome" minlength="2" pattern="^[A-Za-zÀ-ÿ\s]{2,}$" title="O nome deve conter apenas letras e pelo menos 2 caracteres." value="$first_name" required autocomplete="off">
								</div>
							</div>
						</div>
					</div>
					<!-- last_name -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Último Nome</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="text" name="last_name" placeholder="Último Nome" minlength="2" pattern="^[A-Za-zÀ-ÿ\s]{2,}$" title="O sobrenome deve conter apenas letras e pelo menos 2 caracteres." value="$last_name" autocomplete="off">
								</div>
							</div>
						</div>
					</div>
					<!-- address -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Endereço</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="text" name="address" placeholder="Endereço" value="$address" autocomplete="off">
								</div>
							</div>
						</div>
					</div>
					<!-- phone -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Contacto Móvel</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="tel" name="phone" placeholder="Contacto Móvel" pattern="^\+?[0-9\s-]{7,15}$" title="Insira um número de telefone válido, com 7 a 15 dígitos." value="$phone" autocomplete="off" required>
								</div>
							</div>
						</div>
					</div>
					<!-- level -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Nível</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<div class="select">
										<select name="level" autocomplete="off">
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
									<textarea name="notes" class="textarea" placeholder="Notas sobre como o cliente surgiu, ou o seu caracter..." rows="3" autocomplete="off"></textarea>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		
			<div class="field">
				<div class="field-body">
					<div class="field">
						<div class="control buttons">
							<button class="button is-success" type="submit">Criar Cliente</button>
							<button class="button is-danger" type="reset">Limpar</button>
							<a href="clientes.php" class="button is-text">Voltar</a>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</section>
HTML;

include 'footer.php';