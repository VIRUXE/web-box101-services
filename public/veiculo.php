<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

include '../database.php';
include 'header.php';

$matricula = isset($_GET['matricula']) ? $db->real_escape_string($_GET['matricula']) : NULL;

echo <<<HTML
    <section class="section">
        <div class="container">
HTML;
            
if ($matricula) {
    $query = $db->query("
        SELECT v.*, IFNULL(CONCAT(u.first_name, ' ', u.last_name), 'Desconhecido') AS registered_by 
        FROM vehicles v 
        LEFT JOIN users u ON v.registered_by = u.id 
        WHERE v.matricula = '$matricula';
    ");
    
    if ($query->num_rows) {
        include '../Vehicle.class.php';

        $result = $query->fetch_assoc();
        $vehicle = new Vehicle($result);

        $registration_date = date('d/m/Y H:i:s', strtotime($result['registration_date']));

        echo <<<HTML
            <h1 class="title">$vehicle</h1>
            <p class="subtitle">$matricula</p>
            <p class="block"><strong>Notas:</strong> {$vehicle->getNotes()}</p>
            <p class="has-text-grey">Odômetro Inicial: {$vehicle->getOdometer()}</p>
            <p class="has-text-grey-dark">Adcionado por {$result['registered_by']} a $registration_date</p>
            <hr>
            <div class="buttons is-grouped">
                <a href="criar_servico.php?matricula=$matricula" class="button is-success">Criar Serviço</a>
                <a href="editar_veiculo.php?matricula=$matricula" class="button">Editar Veículo</a>
                <a href="eliminar_veiculo.php?matricula=$matricula" class="button">Eliminar Veículo</a>
                <a href="veiculos.php" class="button is-text">Voltar</a>
            </div>
            <hr>
            <h2 class="h2">Serviços</h2>
            <table class="table is-fullwidth is-hoverable is-striped">
                <thead>
                    <tr>
                        <th style="width: 10%;">Data</th>
                        <th style="width: 50%;">Descrição</th>
                        <th style="width: 10%;">Preço</th>
                    </tr>
                </thead>
                <tbody>
        HTML;

        $query = $db->query("SELECT * FROM vehicle_services WHERE matricula = '$matricula';");
        $services_count = $query->num_rows;

        while ($service = $query->fetch_object()) {
            echo <<<HTML
                <tr>
                    <td>{$service->start_date}</td>
                    <td>{$service->description}</td>
                    <td>{$service->price}</td>
                </tr>
            HTML;

            $items = $db->query("SELECT * FROM vehicle_service_items WHERE service_id = {$service->id};");

            while ($item = $items->fetch_object()) {
                echo <<<HTML
                    <tr>
                        <td>{$item->start_date}</td>
                        <td>{$item->status}</td>
                        <td>{$item->start_notes}</td>
                    </tr>
                HTML;
            }
        }

        echo <<<HTML
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6">Total de Serviços: $services_count</th>
                    </tr>
                </tfoot>
            </table>
        HTML;
    } else
        echo '<div class="notification is-danger">Matrícula não encontrada.</div>';
} else
    echo '<div class="notification is-danger">Matrícula não foi fornecida.</div>';

echo <<<HTML
        </div>
    </section>
HTML;

include 'footer.php';
?>