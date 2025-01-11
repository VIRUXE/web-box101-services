<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();
include '../database.php';

// Handle AJAX requests for parts management first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    exit; // Ensure we stop here for AJAX requests
}

include 'header.php';

$id = isset($_GET['id']) ? $db->real_escape_string($_GET['id']) : NULL;

echo <<<HTML
    <section class="section">
        <div class="container">
HTML;

if ($id) {
    $query = $db->query("
        SELECT 
            vs.*,
            v.brand,
            v.model,
            v.matricula as plate,
            CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
            u2.first_name as client_first_name,
            u2.last_name as client_last_name,
            u2.phone as client_phone,
            u2.email as client_email
        FROM vehicle_services vs
        LEFT JOIN vehicles v ON vs.matricula = v.matricula
        LEFT JOIN users u ON vs.created_by = u.id
        LEFT JOIN users u2 ON vs.client_id = u2.id
        WHERE vs.id = $id
    ");

    if ($query->num_rows) {
        $service = $query->fetch_object();
        
        $starting_odometer = $service->starting_odometer ?: 'N/D';
        $finished_odometer = $service->finished_odometer ?: 'N/D';
        
        echo <<<HTML
            <div class="columns is-mobile is-vcentered mb-4">
                <div class="column">
                    <h1 class="title is-3 mb-0">Serviço #{$service->id}</h1>
                    <p class="subtitle is-6 mt-1 mb-0">
                        Criado por <a href="cliente.php?id={$service->created_by}">{$service->created_by_name}</a> em {$service->created_at}
                    </p>
                </div>
                <div class="column is-narrow">
                    <div class="buttons">
                        <a href="editar_servico.php?id=$id" class="button is-primary">
                            <span class="icon">
                                <i class="fas fa-edit"></i>
                            </span>
                            <span>Editar</span>
                        </a>
                        <a href="veiculo.php?matricula={$service->plate}" class="button is-light">
                            <span class="icon">
                                <i class="fas fa-arrow-left"></i>
                            </span>
                            <span>Voltar</span>
                        </a>
                    </div>
                </div>
            </div>

            <h2 class="title is-4">Informações do Veículo</h2>
            <div class="columns is-multiline">
                <div class="column is-6">
                    <p><strong>Marca:</strong> {$service->brand}</p>
                    <p><strong>Modelo:</strong> {$service->model}</p>
                </div>
                <div class="column is-6">
                    <p><strong>Matrícula:</strong> <a href="veiculo.php?matricula={$service->plate}">{$service->plate}</a></p>
                </div>
            </div>

            <h2 class="title is-4 mt-5">Informações do Cliente</h2>
            <div class="columns is-multiline">
                <div class="column is-6">
                    <p><strong>Nome:</strong> <a href="cliente.php?id={$service->client_id}">{$service->client_first_name} {$service->client_last_name}</a></p>
                    <p><strong>Telefone:</strong> <a href="tel:{$service->client_phone}">{$service->client_phone}</a></p>
                </div>
                <div class="column is-6">
                    <p><strong>Email:</strong> <a href="mailto:{$service->client_email}">{$service->client_email}</a></p>
                </div>
            </div>

            <h2 class="title is-4 mt-5">Estado do Serviço</h2>
            <div class="content">
                <p><strong>Estado:</strong> {$service->state}</p>
                <p><strong>Quilometragem Inicial:</strong> {$starting_odometer}</p>
                <p><strong>Quilometragem Final:</strong> {$finished_odometer}</p>
            </div>
HTML;

        $service_id = $service->id;
        require 'items_view.php';
        require 'parts_view.php';

        echo <<<HTML
        </div>
    </section>
HTML;

    } else {
        echo '<div class="notification is-danger">Serviço não encontrado.</div>';
    }
} else {
    echo '<div class="notification is-danger">ID do serviço não foi fornecido.</div>';
}

include 'footer.php';
?>