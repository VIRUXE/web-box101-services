<?php
include '../User.class.php';
include '../Service.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();
include '../database.php';

// Handle AJAX requests for parts management first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) exit;

include 'header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : NULL;

echo <<<HTML
    <section class="section">
        <div class="container">
HTML;

if ($id) {
    $service = Service::getById($id);
    if (!$service) {
        echo '<div class="notification is-danger">Serviço não encontrado</div>';
        exit;
    }

    // Get vehicle info
    $query = $db->query("SELECT brand, model, matricula as plate, colour, trim, notes as vehicle_notes FROM vehicles WHERE matricula = '{$service->getMatricula()}'");
    $vehicle = $query->fetch_object();

    // Get client info
    $query = $db->query("SELECT first_name, last_name, phone, email FROM users WHERE id = {$service->getClientId()}");
    $client = $query->fetch_object();

    // Get parts totals
    $query = $db->query("
        SELECT 
            COALESCE(SUM(customer_price * quantity), 0) as customer_total,
            COALESCE(SUM(supplier_price * quantity), 0) as supplier_total
        FROM vehicle_service_parts
        WHERE service_id = {$service->getId()} AND deleted = 0
    ");
    $parts = $query->fetch_object();

    // Get labor total
    $query = $db->query("SELECT COALESCE(SUM(price), 0) as total FROM vehicle_service_items WHERE service_id = {$service->getId()}");
    $labor = $query->fetch_object();

    $state      = $service->getState();
    $stateLabel = $state->label();
    $stateColor = $state->color();

    $starting_date         = $service->getStartingDate() ?? 'N/D';
    $ending_date           = $service->getEndingDate() ?? 'N/D';
    $total_cost            = $parts->customer_total + $labor->total;
    $paid_amount           = $service->getPaidAmount() ?? 0;
    $minimum_to_break_even = $parts->supplier_total + $labor->total;
    $profit                = $paid_amount - $minimum_to_break_even;

    $starting_odometer = $service->getStartingOdometer() ?: 'N/D';
    $finished_odometer = $service->getFinishedOdometer() ?: 'N/D';

    echo <<<HTML
        <div class="columns is-vcentered mb-4">
            <div class="column">
                <h1 class="title is-3 mb-0">Serviço <span class="has-text-grey-dark">#{$service->getId()}</span></h1>
                <p class="subtitle is-6 is-italic has-text-grey">Criado em {$service->getCreatedAt()}</p>
            </div>
            <div class="column is-narrow">
                <span class="tag is-large has-text-weight-bold"><a href="veiculo.php?matricula={$service->getMatricula()}">{$vehicle->plate}</a></span>
            </div>
            <div class="column is-narrow">
                <span class="tag {$stateColor} is-medium">{$stateLabel}</span>
            </div>
            <div class="column is-narrow">
                <div class="buttons">
                    <a href="imprimir_servico.php?id={$service->getId()}" class="button is-info">
                        <span class="icon is-small"><i class="fas fa-print"></i></span>
                        <span>Imprimir</span>
                    </a>
                    <a href="editar_servico.php?id={$service->getId()}" class="button is-primary">
                        <span class="icon is-small"><i class="fas fa-edit"></i></span>
                        <span>Editar</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="columns">
            <div class="column">
                <div class="box">
                    <h2 class="title is-5">Detalhes do Serviço</h2>
                    <div class="content">
                        <p><strong>Data de Início:</strong> {$starting_date}</p>
                        <p><strong>Data de Fim:</strong> {$ending_date}</p>
                        <p><strong>Quilómetros Iniciais:</strong> {$starting_odometer}</p>
                        <p><strong>Quilómetros Finais:</strong> {$finished_odometer}</p>
                    </div>
                </div>
            </div>
            <div class="column">
                <div class="box">
                    <h2 class="title is-5">Custos</h2>
                    <div class="content">
                        <p><strong>Total em Peças</strong> <span class="tag"><span class="has-text-weight-bold mr-1">Cliente:</span> {$parts->customer_total}€</span> <span class="tag"><span class="has-text-weight-bold mr-1">Fornecedor:</span> {$parts->supplier_total}€</span></p>
                        <p><strong>Total em Mão de Obra:</strong> {$labor->total}€</p>
                        <p><strong>Valor Pago:</strong> {$paid_amount}€ ({$total_cost}€)</p>
                        <p><strong>Lucro:</strong> {$profit}€</p>
                    </div>
                </div>
            </div>
        </div>
HTML;

    $service_id = $service->getId();
    require 'items_view.php';
    require 'parts_view.php';

} else {
    echo '<div class="notification is-danger">ID não especificado</div>';
}

echo <<<HTML
    </div>
</section>
HTML;

include 'footer.php';
?>