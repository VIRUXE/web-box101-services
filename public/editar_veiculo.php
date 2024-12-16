<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if (!$logged_user->isAdmin()) header('Location: index.php');

include 'header.php';

include '../database.php';

echo <<<HTML
<section class="section">
	<div class="container">
HTML;
		
if ($_POST) {
	// matricula, odometer, brand, model are required
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
		$stmt = $db->prepare("UPDATE vehicles SET matricula = ?, odometer = ?, year = ?, month = ?, brand = ?, model = ?, colour = ?, trim = ?, notes = ? WHERE matricula = ?");
		$stmt->bind_param("siiissssss", $matricula, $odometer, $year, $month, $brand, $model, $colour, $trim, $notes, $matricula);
		$stmt->execute();

		if ($db->affected_rows)
			header('location: veiculo.php?matricula=' . $_POST['matricula']);
		else
			echo '<div class="notification is-warning">Não foram feitas alterações.</div>';
	} catch (mysqli_sql_exception $e) {
		$message = '<div class="notification is-danger">Erro ao editar veículo: ';
		
		$message .= match ($e->getCode()) {
			1062    => 'Já existe um veículo com essa matrícula.',
			default => $e->getMessage()
		};
		
		echo $message . '</div>';
	}
}

$matricula = $db->real_escape_string($_GET['matricula']) ?? NULL;

if ($matricula) {
	$query = $db->query("SELECT * FROM vehicles WHERE matricula = '$matricula'");

	if ($query->num_rows) {
		include '../Vehicle.class.php';

		$vehicle = new Vehicle($query->fetch_assoc());

		echo <<<HTML
		<h1 class="title">Editar Veículo</h1>
		<form method="post" class="">
			<div class="columns">
				<div class="column is-5">
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Matrícula</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="text" name="matricula" value="{$vehicle->matricula}" required>
								</div>
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
								<div class="control">
									<input class="input" type="number" name="odometer" value="{$vehicle->odometer}">
								</div>
							</div>
						</div>
					</div>
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Ano</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="number" name="year" value="{$vehicle->year}">
								</div>
							</div>
						</div>
					</div>
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Mês</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="number" name="month" value="{$vehicle->month}">
								</div>
							</div>
						</div>
					</div>
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Marca</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="text" name="brand" value="{$vehicle->brand}" required>
								</div>
							</div>
						</div>
					</div>
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Modelo</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="text" name="model" value="{$vehicle->model}" required>
								</div>
							</div>
						</div>
					</div>
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Cor</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="text" name="colour" value="{$vehicle->colour}">
								</div>
							</div>
						</div>
					</div>
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Versão</label>
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<input class="input" type="text" name="trim" value="{$vehicle->trim}">
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
									<textarea name="notes" class="textarea" placeholder="Notas sobre como o veículo se encontra, ou histórico." rows="3">{$vehicle->notes}</textarea>
								</div>
							</div>
						</div>
					</div>
					<div class="field is-horizontal">
						<div class="field-label">
						</div>
						<div class="field-body">
							<div class="field">
								<div class="control">
									<button class="button is-primary">Editar</button>
									<a class="button is-text" href="veiculo.php?matricula={$vehicle->matricula}">Cancelar</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>

		<script>
			const forceCapitalize = e => e.target.value = e.target.value.toLowerCase().replace(/(?:^|\s)\S/g, (a => a.toUpperCase()))

			['brand', 'model', 'colour', 'trim'].forEach(input => document.querySelector('input[name="' + input + '"]').addEventListener('input', forceCapitalize));

			// Enforce rules on input "matricula". Always uppercase and no special characters (except "-" and spaces)
			document.querySelector('input[name="matricula"]').addEventListener('input', e => e.target.value = e.target.value.replace(/[^A-Z0-9\s-]/gi, '').toUpperCase());

			// Remove leading/trailing spaces on blur
			document.querySelectorAll('input').forEach(input => input.addEventListener('blur', e => e.target.value = e.target.value.trim()));
		</script>
HTML;
	} else echo '<div class="notification is-danger">Veículo não encontrado</div>';
} else echo '<div class="notification is-danger">Matrícula não especificada</div>';

echo <<<HTML
	</div>
</section>
HTML;

include 'footer.php';
?>