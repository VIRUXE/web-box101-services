<?php
include_once '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if (!$logged_user->isAdmin()) header('Location: index.php');

include 'header.php';

$matricula = '';
$year      = '';
$month     = '';
$brand     = '';
$model     = '';
$colour    = '';
$trim      = '';

echo <<<HTML
<section class="section">
	<div class="container">
HTML;

if ($_POST) {
	include '../database.php';

	$matricula = $db->real_escape_string($_POST['matricula']);
	$year      = $db->real_escape_string($_POST['year']) ?: NULL;
	$month     = $db->real_escape_string($_POST['month']) ?: NULL;
	$brand     = $db->real_escape_string($_POST['brand']);
	$model     = $db->real_escape_string($_POST['model']);
	$colour    = $db->real_escape_string($_POST['colour']) ?: NULL;
	$trim      = $db->real_escape_string($_POST['trim']) ?: NULL;

	echo "Matricula: $matricula<br>Year: $year<br>Month: $month<br>Brand: $brand<br>Model: $model<br>Colour: $colour<br>Trim: $trim<br>";

	try {
		$stmt = $db->prepare('INSERT INTO cars (matricula, year, month, brand, model, colour, trim) VALUES (?, ?, ?, ?, ?, ?, ?)');

		$stmt->bind_param('siissss', $matricula, $year, $month, $brand, $model, $colour, $trim);

		$stmt->execute();

		if ($db->affected_rows)
			// echo '<div class="notification is-success">Carro adicionado com sucesso!</div>';
			header('location: carro.php?matricula=' . $matricula);
		else
			echo '<div class="notification is-danger">Erro ao adicionar carro!</div>';

	} catch (mysqli_sql_exception $e) {
		echo match ($e->getCode()) {
			1062    => '<div class="notification is-danger">Erro ao adicionar carro: Esse carro já está registado.</div>',
			default => '<div class="notification is-danger">Erro ao adicionar carro: ' . $e->getMessage() . '</div>',
		};
	}
} else { // Display form
	echo <<<HTML
		<h1 class="title">Adicionar Carro</h1>
		<form method="post">
			<div class="columns is-mobile">
				<div class="column is-4">
					<!-- Matricula -->
					<div class="field is-horizontal">
						<div class="field-label is-normal">
							<label class="label">Matrícula</label>
						</div>
						<div class="field-body">
							<div class="field">
								<p class="control">
									<input class="input" type="text" name="matricula" value="$matricula" onkeyup="forceUppercase(event)" required>
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
									<input class="input" type="number" name="year" value="$year">
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
									<input class="input" type="number" name="month" value="$month">
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
									<input class="input" type="text" name="brand" value="$brand" onkeyup="forceCapitalize(event)" required>
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
									<input class="input" type="text" name="trim" value="$trim" onkeyup="forceCapitalize(event)">
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Botões -->
			<div class="field is-grouped">
				<div class="control">
					<button class="button is-primary">Adicionar</button>
				</div>
				<div class="control">
					<button type="reset" class="button is-warning">Limpar</button>
				</div>
				<div class="control">
					<a href="carros.php" class="button">Voltar</a>
				</div>
			</div>
		</form>
HTML;
}

echo <<<HTML
	</div>
</section>

<script>
	const forceCapitalize = (e) => e.target.value = e.target.value.toLowerCase().replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); });
	const forceUppercase  = (e) => e.target.value = e.target.value.toUpperCase();
</script>
HTML;

include 'footer.php';
?>
