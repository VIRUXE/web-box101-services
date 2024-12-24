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
            <h2 class="title is-4">Serviços</h2>
            <div class="table-container">
                <table class="table is-fullwidth is-hoverable is-striped is-narrow">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Data</th>
                            <th>Kms</th>
                            <th>Estado</th>
                            <th class="is-hidden-mobile">Status</th>
                            <th class="is-hidden-mobile">Criado por</th>
                            <th>Preço</th>
                        </tr>
                    </thead>
                    <tbody>
        HTML;

        $query = $db->query("
            SELECT 
                vs.id,
                vs.state,
                vs.active,
                vs.starting_odometer,
                DATE_FORMAT(vs.starting_date, '%d/%m/%Y') as start_date,
                CONCAT(u.first_name, ' ', u.last_name) as created_by,
                COALESCE(SUM(vsi.price), 0) as total_price
            FROM vehicle_services vs
            LEFT JOIN vehicle_service_items vsi ON vs.id = vsi.service_id
            LEFT JOIN users u ON vs.created_by = u.id
            WHERE vs.matricula = '$matricula'
            GROUP BY vs.id
            ORDER BY vs.starting_date DESC
        ");

        $services_count = $query->num_rows;

        while ($service = $query->fetch_object()) {
            $state_tag = match($service->state) {
                'PENDING' => 'is-warning',
                'PROPOSAL' => 'is-light',
                'AWAITING_APPROVAL' => 'is-warning',
                'APPROVED' => 'is-success',
                'IN_PROGRESS' => 'is-info',
                'COMPLETED' => 'is-success',
                'CANCELLED' => 'is-danger',
                default => 'is-light'
            };
            
            $state_text = match($service->state) {
                'PENDING' => 'Pendente',
                'PROPOSAL' => 'Proposta',
                'AWAITING_APPROVAL' => 'Aguarda Aprovação',
                'APPROVED' => 'Aprovado',
                'IN_PROGRESS' => 'Em Progresso',
                'COMPLETED' => 'Concluído',
                'CANCELLED' => 'Cancelado',
                default => $service->state
            };
            
            $active_tag = $service->active ? 'is-success' : 'is-danger';
            $active_text = $service->active ? 'Ativo' : 'Inativo';
            
            echo <<<HTML
                <tr>
                    <td><a href="servico.php?id={$service->id}">{$service->id}</a></td>
                    <td>{$service->start_date}</td>
                    <td>{$service->starting_odometer}</td>
                    <td><span class="tag $state_tag is-normal">$state_text</span></td>
                    <td class="is-hidden-mobile"><span class="tag $active_tag is-normal">$active_text</span></td>
                    <td class="is-hidden-mobile">{$service->created_by}</td>
                    <td>{$service->total_price}€</td>
                </tr>
            HTML;
        }

        echo <<<HTML
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="8">Total de Serviços: $services_count</th>
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