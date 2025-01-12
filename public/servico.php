<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();
include '../database.php';

// Handle AJAX requests for parts management first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) exit;

include 'header.php';

$id = isset($_GET['id']) ? $db->real_escape_string($_GET['id']) : NULL;

echo <<<HTML
    <section class="section">
        <div class="container">
HTML;

if ($id) {
    $query = $db->query("
        SELECT 
            service.*,
            vehicle.brand,
            vehicle.model,
            vehicle.matricula as plate,
            vehicle.colour,
            vehicle.trim,
            vehicle.notes as vehicle_notes,
            GREATEST(
                COALESCE(service.created_at, '1000-01-01'),
                COALESCE((SELECT MAX(GREATEST(
                    COALESCE(serviceItem.created_at, '1000-01-01'),
                    COALESCE(serviceItem.end_date, '1000-01-01')
                )) FROM vehicle_service_items serviceItem WHERE serviceItem.service_id = service.id), '1000-01-01'),
                COALESCE((SELECT MAX(GREATEST(
                    COALESCE(itemTracking.start_date, '1000-01-01'),
                    COALESCE(itemTracking.end_date, '1000-01-01')
                )) FROM vehicle_service_item_tracking itemTracking 
                JOIN vehicle_service_items serviceItem ON itemTracking.service_item_id = serviceItem.id 
                WHERE serviceItem.service_id = service.id), '1000-01-01'),
                COALESCE((SELECT MAX(created_at) 
                FROM vehicle_service_parts servicePart 
                WHERE servicePart.service_id = service.id), '1000-01-01')
            ) as last_update,
            creator.first_name as created_by_name,
            client.first_name as client_first_name,
            client.last_name as client_last_name,
            client.phone as client_phone,
            client.email as client_email,
            service.created_at,
            COALESCE(SUM(servicePart.customer_price), 0) as parts_total,
            COALESCE(SUM(serviceItem.price), 0) as labor_total,
            (COALESCE(SUM(servicePart.customer_price), 0) + COALESCE(SUM(serviceItem.price), 0)) as total_cost
        FROM vehicle_services service
        LEFT JOIN vehicles vehicle ON service.matricula = vehicle.matricula
        LEFT JOIN users creator ON service.created_by = creator.id
        LEFT JOIN users client ON service.client_id = client.id
        LEFT JOIN vehicle_service_parts servicePart ON service.id = servicePart.service_id
        LEFT JOIN vehicle_service_items serviceItem ON service.id = serviceItem.service_id
        WHERE service.id = $id
        GROUP BY service.id, vehicle.brand, vehicle.model, vehicle.matricula, vehicle.colour, vehicle.trim, vehicle.notes, creator.first_name, client.first_name, client.last_name, client.phone, client.email, service.created_at
    ");

    if ($query->num_rows) {
        $service = $query->fetch_object();
        
        $starting_odometer = $service->starting_odometer ?: 'N/D';
        $finished_odometer = $service->finished_odometer ?: 'N/D';

        $total_parts_cost = 0;
        $total_supplier_cost = 0;

        // Get parts information
        $parts_stmt = $db->prepare("
            SELECT 
                SUM(customer_price * quantity) as total_parts_cost,
                SUM(supplier_price * quantity) as total_supplier_cost,
                SUM(CASE WHEN supplier_paid = 0 THEN supplier_price * quantity ELSE 0 END) as unpaid_supplier_cost
            FROM vehicle_service_parts 
            WHERE service_id = ?
        ");
        $parts_stmt->bind_param('i', $service->id);
        $parts_stmt->execute();
        $parts_result = $parts_stmt->get_result();

        if ($parts_row = $parts_result->fetch_object()) {
            $total_parts_cost     = $parts_row->total_parts_cost ?? 0;
            $total_supplier_cost  = $parts_row->total_supplier_cost ?? 0;
            $unpaid_supplier_cost = $parts_row->unpaid_supplier_cost ?? 0;
            $paid_supplier_cost   = $total_supplier_cost - $unpaid_supplier_cost;
        }

        $formatted_labor_total     = number_format($service->labor_total ?? 0, 2, ',', '.');
        $formatted_supplier_cost   = number_format($total_supplier_cost, 2, ',', '.');
        $formatted_paid_supplier   = number_format($paid_supplier_cost, 2, ',', '.');
        $formatted_unpaid_supplier = number_format($unpaid_supplier_cost, 2, ',', '.');
        $formatted_profit          = number_format(($total_parts_cost + ($service->labor_total ?? 0)) - $total_supplier_cost, 2, ',', '.');
        $formatted_customer_total  = number_format($total_parts_cost + ($service->labor_total ?? 0), 2, ',', '.');

        $status = match($service->state) {
            'PENDING' => ['text' => 'Pendente', 'class' => 'is-warning'],
            'PROPOSAL' => ['text' => 'Proposta', 'class' => 'is-info'],
            'AWAITING_APPROVAL' => ['text' => 'Aguarda Aprovação', 'class' => 'is-warning'],
            'APPROVED' => ['text' => 'Aprovado', 'class' => 'is-success'],
            'IN_PROGRESS' => ['text' => 'Em Progresso', 'class' => 'is-info'],
            'COMPLETED' => ['text' => 'Concluído', 'class' => 'is-success'],
            'CANCELLED' => ['text' => 'Cancelado', 'class' => 'is-danger'],
            default => ['text' => $service->state, 'class' => 'is-light'],
        };

        echo <<<HTML
            <div class="columns is-vcentered mb-4">
                <div class="column">
                    <h1 class="title is-3 mb-0">Serviço #{$service->id}</h1>
                </div>
                <div class="column is-12-mobile is-narrow-tablet">
                    <div class="buttons is-right">
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

            <div class="columns is-multiline">
                <!-- Service Status -->
                <div class="column is-half is-flex">
                    <div class="box is-flex-grow-1">
                        <h2 class="title is-4">Informação do Veículo</h2>
                        <div class="content">
                            <p><strong>Marca:</strong> {$service->brand}</p>
                            <p><strong>Modelo:</strong> {$service->model}</p>
                            <p><strong>Matrícula:</strong> {$service->plate}</p>
                            <p><strong>Odómetro Inicial:</strong> {$starting_odometer}</p>
                            <p><strong>Odómetro Final:</strong> {$finished_odometer}</p>
                        </div>
                    </div>
                </div>

                <!-- Service Costs Overview -->
                <div class="column is-half is-flex">
                    <div class="box is-flex-grow-1">
                        <h2 class="title is-4">Informação do Cliente</h2>
                        <div class="content">
                            <p><strong>Nome:</strong> {$service->client_first_name} {$service->client_last_name}</p>
                            <p><strong>Telefone:</strong> {$service->client_phone}</p>
                            <p><strong>Email:</strong> {$service->client_email}</p>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Info -->
                <div class="column is-half is-flex">
                    <div class="box is-flex-grow-1">
                        <h2 class="title is-4">Estado do Serviço</h2>
                        <div class="content">
                            <p><strong>Estado:</strong> {$service->state}</p>
                            <p><strong>Criado por:</strong> {$service->created_by_name}</p>
                            <p><strong>Criado em:</strong> {$service->created_at}</p>
                            <p><strong>Última atualização:</strong> {$service->last_update}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Client Info -->
                <div class="column is-half is-flex">
                    <div class="box is-flex-grow-1">
                        <h2 class="title is-4">Resumo dos Custos do Serviço</h2>
                        <div class="content">
                            <p><strong>Total de Peças (Custo):</strong> €{$formatted_supplier_cost}</p>
                            <p><strong>Custo Total de Peças (Pago/Por Pagar):</strong> €{$formatted_supplier_cost} / €{$formatted_unpaid_supplier}</p>
                            <p><strong>Lucro Total:</strong> €{$formatted_profit}</p>
                            <p><strong>Custo Total para o Cliente:</strong> €{$formatted_customer_total}</p>
                        </div>
                    </div>
                </div>
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