<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

include '../database.php';
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
            v.matricula,
            (SELECT COUNT(*) 
             FROM vehicle_services vs2 
             WHERE vs2.matricula = vs.matricula 
             AND vs2.id <= vs.id) as vehicle_service_number
        FROM vehicle_services vs
        LEFT JOIN vehicles v ON vs.matricula = v.matricula
        WHERE vs.id = $id
    ");

    if ($query->num_rows) {
        $service = $query->fetch_object();
        
        $state_tag = match($service->state) {
            'PENDING'           => 'is-warning',
            'PROPOSAL'          => 'is-light',
            'AWAITING_APPROVAL' => 'is-warning',
            'APPROVED'          => 'is-success',
            'IN_PROGRESS'       => 'is-info',
            'COMPLETED'         => 'is-success',
            'CANCELLED'         => 'is-danger',
            default             => 'is-light'
        };
        
        $state_text = match($service->state) {
            'PENDING'           => 'Pendente',
            'PROPOSAL'          => 'Proposta',
            'AWAITING_APPROVAL' => 'Aguarda Aprovação',
            'APPROVED'          => 'Aprovado',
            'IN_PROGRESS'       => 'Em Progresso',
            'COMPLETED'         => 'Concluído',
            'CANCELLED'         => 'Cancelado',
            default             => $service->state
        };
        
        $active_tag = $service->active ? 'is-success' : 'is-danger';
        $active_text = $service->active ? 'Ativo' : 'Inativo';
        $starting_odometer_text = $service->starting_odometer ? "$service->starting_odometer km": 'N/D';
        $finished_odometer_text = $service->finished_odometer ? "$service->finished_odometer km" : 'N/D';

        echo <<<HTML
            <h1 class="title">Serviço #{$service->id}</h1>
            <p class="subtitle">Veículo: <a href="veiculo.php?matricula={$service->matricula}">{$service->matricula}</a></p>
            <p class="has-text-grey is-size-7">Serviço #{$service->vehicle_service_number} deste veículo</p>
            
            <div class="columns">
                <div class="column">
                    <p><strong>Quilometragem Inicial:</strong> {$starting_odometer_text}</p>
                    <p><strong>Quilometragem Final:</strong> {$finished_odometer_text}</p>
                </div>
                <div class="column">
                    <p><strong>Estado:</strong> <span class="tag $state_tag">$state_text</span></p>
                    <p><strong>Status:</strong> <a class="tag $active_tag is-tooltip-multiline" data-tooltip="Indica se o serviço foi excluído ou não">$active_text</a></p>
                </div>
            </div>

            <div class="buttons is-grouped">
                <a href="editar_servico.php?id=$id" class="button is-info">Editar Serviço</a>
                <a href="veiculo.php?matricula={$service->matricula}" class="button is-text">Voltar</a>
            </div>
        HTML;

        $items_query = $db->query("
            SELECT
				vsi.id,
                vsi.status,
                vsi.price,
                vsi.description,
                vsi.start_notes IS NOT NULL as has_starting_notes,
                vsi.end_notes IS NOT NULL as has_ending_notes
            FROM vehicle_service_items vsi
            WHERE vsi.service_id = $id
            ORDER BY vsi.id ASC
        ");

        $total_price = 0;

        while ($item = $items_query->fetch_object()) $total_price += $item->price;

        echo <<<HTML
            <hr>
            <div class="is-flex is-justify-content-space-between is-align-items-center">
                <h2 class="title is-4 mb-0">Items do Serviço</h2>
                <div class="has-text-weight-bold is-size-4">{$total_price}€</div>
            </div>
            <div class="table-container">
                <table class="table is-fullwidth is-hoverable is-striped is-narrow">
                    <thead>
                        <tr>
                            <th style="width: 50%">Descrição</th>
                            <th style="width: 120px">Estado</th>
                            <th style="width: 100px">Preço</th>
                            <th style="width: 120px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
        HTML;

        $items_query->data_seek(0);

        while ($item = $items_query->fetch_object()) {
            $actions = '';
            
            if ($item->status == 'NOT_STARTED') $actions .= '<a href="#" class="button is-small">Iniciar</a>';
            
            if ($item->status == 'PAUSED') $actions .= '<a href="#" class="button is-small">Retomar</a>';
            
            if ($item->status == 'STARTED') $actions .= '<a href="#" class="button is-small">Pausar</a>';
            
            if ($item->status == 'STARTED' || $item->status == 'PAUSED') $actions .= '<a href="#" class="button is-small">Terminar</a>';

            $status_tag = match($item->status) {
                'NOT_STARTED' => 'is-light',
                'STARTED'     => 'is-info',
                'PAUSED'      => 'is-warning',
                'FAILED'      => 'is-danger',
                'SUCCESS'     => 'is-success',
                default       => 'is-light'
            };

            $status_text = match($item->status) {
                'NOT_STARTED' => 'Não Iniciado',
                'STARTED'     => 'Em Curso',
                'PAUSED'      => 'Pausado',
                'FAILED'      => 'Falhou',
                'SUCCESS'     => 'Concluído',
                default       => $item->status
            };

            $starting_notes = $item->has_starting_notes ? '<span class="tag is-info is-light">Notas Iniciais</span>' : '';
            $ending_notes   = $item->has_ending_notes ? '<span class="tag is-success is-light">Notas Finais</span>' : '';

            echo <<<HTML
                <tr>
                    <td>
                        <a href="item_servico.php?id={$item->id}">{$item->description}</a>
                        {$starting_notes}
                        {$ending_notes}
                    </td>
                    <td><span class="tag $status_tag">$status_text</span></td>
                    <td>{$item->price}€</td>
                    <td>{$actions}</td>
                </tr>
            HTML;
        }

        echo <<<HTML
                    </tbody>
                </table>
            </div>
            
            <hr>
            <div class="is-flex is-justify-content-space-between is-align-items-center">
                <h2 class="title is-4 mb-0">Peças do Serviço</h2>
                <div>
                    <a href="#" class="button is-primary is-small">Adicionar Peça</a>
                </div>
            </div>
HTML;

        $parts_query = $db->query("
            SELECT
                vsp.*,
                CONCAT(u.first_name, ' ', u.last_name) as added_by_name
            FROM vehicle_service_parts vsp
            LEFT JOIN users u ON vsp.added_by = u.id
            WHERE vsp.service_id = $id
            ORDER BY vsp.id DESC
        ");

        $total_parts_cost = 0;
        $total_supplier_cost = 0;

        echo <<<HTML
            <div class="table-container">
                <table class="table is-fullwidth is-hoverable is-striped is-narrow">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th style="width: 100px">Qtd.</th>
                            <th style="width: 120px">Preço Cliente</th>
                            <th style="width: 120px">Custo Forn.</th>
                            <th style="width: 120px">Origem</th>
                        </tr>
                    </thead>
                    <tbody>
HTML;

        if ($parts_query->num_rows) {
            while ($part = $parts_query->fetch_object()) {
                $total_parts_cost += $part->customer_price * $part->quantity;
                $total_supplier_cost += $part->supplier_price ? ($part->supplier_price * $part->quantity) : 0;
                
                $supplier_info = $part->supplier_price ? 
                    number_format($part->supplier_price, 2) . '€' . 
                    ($part->supplier_discount ? " (-{$part->supplier_discount}%)" : '') : 
                    'N/D';

                echo <<<HTML
                    <tr>
                        <td>
                            {$part->description}
                            <div class="has-text-grey is-size-7">Adicionado por: {$part->added_by_name}</div>
                        </td>
                        <td>{$part->quantity}</td>
                        <td>{$part->customer_price}€</td>
                        <td>{$supplier_info}</td>
                        <td>{$part->origin}</td>
                    </tr>
HTML;
            }
            
            echo <<<HTML
                    <tr class="has-background-light has-text-weight-bold">
                        <td colspan="2">Total</td>
                        <td>{$total_parts_cost}€</td>
                        <td>{$total_supplier_cost}€</td>
                        <td></td>
                    </tr>
HTML;
        } else {
            echo '<tr><td colspan="5" class="has-text-centered">Sem peças registadas</td></tr>';
        }

        echo <<<HTML
                    </tbody>
                </table>
            </div>
HTML;
    } else
        echo '<div class="notification is-danger">Serviço não encontrado.</div>';
} else
    echo '<div class="notification is-danger">ID do serviço não foi fornecido.</div>';

echo <<<HTML
        </div>
    </section>
HTML;

include 'footer.php';
?>
