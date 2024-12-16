<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

include 'header.php';
include '../database.php';

const BASE_QUERY = "SELECT * FROM vehicles";

$search = isset($_GET['pesquisa']) ? $db->real_escape_string($_GET['pesquisa']) : NULL;

$query = $db->query(BASE_QUERY . ($search ? " WHERE matricula LIKE '%$search%' OR brand LIKE '%$search%' OR model LIKE '%$search%' OR colour LIKE '%$search%' OR trim LIKE '%$search%'" : "") . ' ORDER BY registration_date DESC;');

echo <<<HTML
    <section class="section">
        <div class="container">
HTML;

$vehicle_count = $query->num_rows;

if ($search) {
    if (!$vehicle_count)
        echo '<div class="notification is-warning">Não foram encontrados veículos com o termo de pesquisa "<strong>'.$search.'</strong>".</div>';
    else {
        echo '<div class="notification is-success">'. match ($vehicle_count) {
            1       => 'Foi encontrado <strong>1</strong> veículo com o termo de pesquisa "<strong>'.$search.'</strong>".',
            default => 'Foram encontrados <strong>'.$vehicle_count.'</strong> veículos com o termo de pesquisa "<strong>'.$search.'</strong>".'
        }.'</div>';
    }
}

echo <<<HTML
            <h1 class="title">Pesquisar por Veículos</h1>
            <form method="get">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input class="input" type="text" name="pesquisa" placeholder="Matrícula, Marca, Modelo, Cor, Nome do Cliente" value="$search" minlength="2" required>
                    </div>
                    <div class="control">
                        <button class="button is-info" type="submit">Pesquisar</button>
                    </div>
                </div>
            </form>
            <hr>
            <div class="buttons is-grouped">
                <a href="criar_veiculo.php" class="button">Criar Veículo</a>
                <a href="{$_SERVER['HTTP_REFERER']}" class="button is-text">Voltar</a>
            </div>
            <hr>
HTML;

if ($vehicle_count) {
    include '../Vehicle.class.php';

    echo <<<HTML
    <div class="table-container">
            <table class="table is-fullwidth is-hoverable is-striped">
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Ultimo Dono</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Cor</th>
                        <th>Versão</th>
                    </tr>
                </thead>
                <tbody>
    HTML;

    while ($result = $query->fetch_assoc()) {
        $vehicle = new Vehicle($result);

        $owner_anchor = isset($vehicle->owner) ? '<a href="cliente.php?id='.$vehicle->owner->id.'">'.$vehicle->owner.'</a>' : 'N/D';
        
        echo <<<HTML
                    <tr>
                        <td><a href="veiculo.php?matricula={$vehicle->matricula}">{$vehicle->matricula}</a></td>
                        <td>$owner_anchor</a></td>
                        <td>{$vehicle->brand}</td>
                        <td>{$vehicle->model}</td>
                        <td>{$vehicle->getColour()}</td>
                        <td>{$vehicle->getTrim()}</td>
                    </tr>
        HTML;
    }

    echo <<<HTML
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6">Total de Veículos: $vehicle_count</th>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>
    </section>
    HTML;

    include 'footer.php';
}