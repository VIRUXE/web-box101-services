<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

include 'header.php';
include '../database.php';

const BASE_QUERY = "SELECT * FROM cars";

$search = isset($_GET['pesquisa']) ? $db->real_escape_string($_GET['pesquisa']) : NULL;

$query = $db->query(BASE_QUERY . ($search ? " WHERE matricula LIKE '%$search%' OR brand LIKE '%$search%' OR model LIKE '%$search%' OR colour LIKE '%$search%' OR trim LIKE '%$search%'" : "") . ';');

// Search by car owner name if no cars were found
// Tables: cars, users (first_name, last_name), user_cars (owner_id)
if ($search && !$query->num_rows)
    $query = $db->query(
        <<<SQL
        SELECT c.* FROM cars c 
        JOIN user_cars uc ON c.matricula = uc.matricula 
        JOIN users u ON uc.owner_id = u.id 
        WHERE u.first_name 
        LIKE '%$search%' OR u.last_name LIKE '%$search%';
        SQL
    );

echo <<<HTML
    <section class="section">
        <div class="container">
HTML;

$count = $query->num_rows;

if ($search) {
    if (!$count)
        echo '<div class="notification is-warning">Não foram encontrados carros com o termo de pesquisa "<strong>'.$search.'</strong>".</div>';
    else {
        $message = '<div class="notification is-success">';
        
        if ($count === 1)
            $message .= 'Foi encontrado <strong>1</strong> carro com o termo de pesquisa "<strong>'.$search.'</strong>".';
        else
            $message .= 'Foram encontrados <strong>'.$count.'</strong> carros com o termo de pesquisa "<strong>'.$search.'</strong>".';

        echo $message.'</div>';
    }
}

echo <<<HTML
            <h1 class="title">Pesquisar por Carros</h1>
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
            <a href="adicionar_carro.php" class="button is-success">Adicionar Carro</a>
            <a href="index.php" class="button is-info">Voltar</a>
            <hr>
HTML;

if ($count) {
    include '../Car.class.php';

    echo <<<HTML
            <table class="table is-fullwidth">
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Dono</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Cor</th>
                        <th>Acabamento</th>
                    </tr>
                </thead>
                <tbody>
    HTML;

    while ($result = $query->fetch_assoc()) {
        $car = new Car($result);

        $owner_anchor = isset($car->owner) ? '<a href="cliente.php?id='.$car->owner->id.'">'.$car->owner.'</a>' : 'N/D';
        
        echo <<<HTML
                    <tr>
                        <td><a href="carro.php?matricula={$car->matricula}">{$car->matricula}</a></td>
                        <td>$owner_anchor</a></td>
                        <td>{$car->brand}</td>
                        <td>{$car->model}</td>
                        <td>{$car->getColour()}</td>
                        <td>{$car->getTrim()}</td>
                    </tr>
        HTML;
    }

    echo <<<HTML
                </tbody>
            </table>
        </div>
    </section>
    HTML;
}