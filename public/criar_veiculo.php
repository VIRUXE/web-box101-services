<?php
include_once '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if (!$logged_user->isAdmin()) header('Location: index.php');

include '../database.php';
include 'header.php';

$matricula = '';
$odometer  = '';
$year      = '';
$month     = '';
$brand     = '';
$model     = '';
$colour    = '';
$trim      = '';

include 'components/brands_datalist.php';

echo <<<HTML
<section class="section">
	<div class="container">
HTML;

if ($_POST) {
	// matricula, brand, model are required
	// year, month, colour, trim and notes are optional
	$matricula = $_POST['matricula'];
	$odometer  = $_POST['odometer'];
	$year      = !empty($_POST['year']) ? $_POST['year'] : NULL;
	$month     = !empty($_POST['month']) ? $_POST['month'] : NULL;
	$brand     = $_POST['brand'];
	$model     = $_POST['model'];
	$colour    = !empty($_POST['colour']) ? $_POST['colour'] : NULL;
	$trim      = !empty($_POST['trim']) ? $_POST['trim'] : NULL;
	$notes     = !empty($_POST['notes']) ? $_POST['notes'] : NULL;
	
	try {
		$stmt = $db->prepare('INSERT INTO vehicles (matricula, odometer, year, month, brand, model, colour, trim, notes, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
		$stmt->bind_param('siiisssssi', $matricula, $odometer, $year, $month, $brand, $model, $colour, $trim, $notes, $logged_user->id);
		$stmt->execute();

		if ($db->affected_rows)
			header('location: veiculo.php?matricula=' . $matricula);
		else
			echo '<div class="notification is-danger">Erro ao adicionar veículo!</div>';
	} catch (mysqli_sql_exception $e) {
		echo '<div class="notification is-danger">Erro ao adicionar veículo: ' . match ($e->getCode()) {
			1062    => 'Esse veículo já está registado',
			default => $e->getMessage()
		} . '</div>';
	}
}

echo <<<HTML
		<h1 class="title">Criar Veículo</h1>
		<form method="post">
			<div class="columns">
				<div class="column is-5">
					<!-- Matricula -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Matrícula</label>
						</div>
						<div class="field-body">
							<div class="field">
								<p class="control">
									<input class="input" type="text" name="matricula" value="$matricula" autocomplete="off" placeholder="Qualquer tipo de matricula" required>
								</p>
							</div>
						</div>
					</div>
					<!-- Odómetro -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Odómetro</label>
						</div>
						<div class="field-body">
							<div class="field">
								<p class="control">
									<input class="input" type="number" name="odometer" value="$odometer" min="0" step="100000" placeholder="0 ou mais" required>
								</p>
							</div>
						</div>					
					</div>
					<!-- Ano -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Ano</label>
						</div>
						<div class="field-body">
							<div class="field">
								<p class="control">
									<input class="input" type="number" name="year" value="$year" minlength="2" maxlength="4"5pattern="\d{2}|\d{4}" max="9999" step="1" placeholder="00 ou 0000">
								</p>
							</div>
						</div>
					</div>
					<!-- Mes -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Mês</label>
						</div>
						<div class="field-body">
							<div class="field">
								<p class="control">
									<input class="input" type="number" name="month" value="$month" min="1" max="12" placeholder="Apenas números">
								</p>
							</div>
						</div>
					</div>
					<!-- Marca -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Marca</label>
						</div>
						<div class="field-body">
							<div class="field">
								<p class="control">

									<input class="input" type="text" id="brand"name="brand" value="$brand" onkeyup="forceCapitalize(event)" required>
								</p>
							</div>
						</div>
					</div>
					<!-- Modelo -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Modelo</label>
						</div>
						<div class="field-body">
							<div class="field">
								<p class="control">
									<input class="input" type="text" name="model" value="$model" onkeyup="forceCapitalize(event)" required>
								</p>
							</div>
						</div>
					</div>
					<!-- Cor -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Cor</label>
						</div>
						<div class="field-body">
							<div class="field">
								<p class="control">
									<input class="input" type="text" name="colour" value="$colour" onkeyup="forceCapitalize(event)">
								</p>
							</div>
						</div>
					</div>
					<!-- Versao -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Versão</label>
						</div>
						<div class="field-body">
							<div class="field">
								<p class="control">
									<input class="input" type="text" name="trim" value="$trim" placeholder="Type R, S, RS, etc. Ou código de chassi">
								</p>
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
									<textarea name="notes" class="textarea" placeholder="Notas sobre como o veículo se encontra, ou histórico." rows="3"></textarea>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Botões -->
			<div class="field is-grouped">
				<div class="control">
					<button class="button is-primary">Criar</button>
				</div>
				<div class="control">
					<button type="reset" class="button is-danger">Limpar</button>
				</div>
				<div class="control">
					<a href="veiculos.php" class="button is-text">Voltar</a>
				</div>
			</div>
		</form>
	</div>
</section>
<script>
	const forceCapitalize = e => e.target.value = e.target.value.toLowerCase().replace(/(?:^|\s)\S/g, a => a.toUpperCase());

	// Enforce rules on "year" input. Must be 2 or 4 digits
	document.querySelector('input[name="year"]').addEventListener('input', e => e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4));

	// Enforce rules on input "matricula". Always uppercase and no special characters (except "-" and spaces)
	document.querySelector('input[name="matricula"]').addEventListener('input', e => e.target.value = e.target.value.replace(/[^A-Z0-9\s-]/gi, '').toUpperCase());

	// Enforce the first letter of "trim" to be uppercase
    document.querySelector('input[name="trim"]').addEventListener('input', e => e.target.value = e.target.value.charAt(0).toUpperCase() + e.target.value.slice(1));

	// Remove leading/trailing spaces on blur, for all inputs
	document.querySelectorAll('input').forEach(input => input.addEventListener('blur', e => e.target.value = e.target.value.trim()));
</script>
HTML;

include 'footer.php';
?>
