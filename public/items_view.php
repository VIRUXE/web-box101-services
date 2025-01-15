<?php
$items_query = $db->query("
    SELECT
        vsi.*,
        CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
        vs.state as service_state
    FROM vehicle_service_items vsi
    LEFT JOIN users u ON vsi.created_by = u.id
    LEFT JOIN vehicle_services vs ON vsi.service_id = vs.id
    WHERE vsi.service_id = {$service_id}
    ORDER BY vsi.id DESC
");

$total_items_cost = 0;

echo <<<HTML
    <hr>
    <h2 class="title is-4">Itens</h2>
    <div class="table-container">
        <table class="table is-fullwidth is-hoverable is-striped is-narrow is-vcentered">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th class="has-text-right" style="width: 120px">Estado</th>
                    <th class="has-text-right" style="width: 100px">Preço</th>
                    <th class="has-text-right" style="width: 120px">Ações</th>
                </tr>
            </thead>
            <tbody>
HTML;

if (!$items_query->num_rows) {
    echo '<tr><td colspan="4" class="has-text-centered">Sem items registados</td></tr>';
    echo <<<HTML
            </tbody>
        </table>
    </div>
    HTML;
    return;
}

while ($item = $items_query->fetch_object()) {
    $total_items_cost += $item->price;
        
    $actions = '';
    if ($item->service_state !== 'PENDING' && $item->service_state !== 'AWAITING_APPROVAL') {
        if ($item->status == 'NOT_STARTED') $actions .= "<button class='button is-info is-small action-btn' data-action='start' data-item-id='{$item->id}'><span class='icon is-small'><i class='fas fa-play'></i></span></button>";
        if ($item->status == 'PAUSED') $actions .= "<button class='button is-info is-small action-btn' data-action='resume' data-item-id='{$item->id}'><span class='icon is-small'><i class='fas fa-play'></i></span></button>";
        if ($item->status == 'STARTED') $actions .= "<button class='button is-warning is-small action-btn' data-action='pause' data-item-id='{$item->id}'><span class='icon is-small'><i class='fas fa-pause'></i></span></button>";
        if ($item->status == 'STARTED' || $item->status == 'PAUSED') $actions .= "<button class='button is-success is-small action-btn' data-action='finish' data-item-id='{$item->id}'><span class='icon is-small'><i class='fas fa-check'></i></span></button>";
    } else
        $actions = '<span class="tag is-info is-light">A aguardar aprovação</span>';

    $status_tag = match($item->status) {
        'NOT_STARTED' => 'is-white',
        'STARTED'     => 'is-info',
        'PAUSED'      => 'is-warning',
        'FAILED'      => 'is-danger',
        'SUCCESS'     => 'is-success',
        default       => 'is-light'
    };

    $status_text = match($item->status) {
        'NOT_STARTED' => 'Por Iniciar',
        'STARTED'     => 'Em Curso',
        'PAUSED'      => 'Em Pausa',
        'FAILED'      => 'Falhou',
        'SUCCESS'     => 'Concluído',
        default       => $item->status
    };

    $starting_notes = $item->start_notes ? '<span class="tag is-info is-light">Notas Iniciais</span>' : '';
    $ending_notes = $item->end_notes ? '<span class="tag is-success is-light">Notas Finais</span>' : '';

    echo <<<HTML
        <tr>
            <td>
                <div>
                    <p class="has-text-weight-medium mb-1">{$item->description}</p>
                    <p class="has-text-grey is-size-7">Adicionado por {$item->created_by_name}</p>
                </div>
                {$starting_notes}
                {$ending_notes}
            </td>
            <td class="has-text-right is-vcentered"><span class="tag {$status_tag}">{$status_text}</span></td>
            <td class="has-text-right is-vcentered">{$item->price}€</td>
            <td class="has-text-right is-vcentered">
                <div class="buttons are-small is-justify-content-flex-end">
                    {$actions}
                </div>
            </td>
        </tr>
    HTML;
}
    
echo <<<HTML
    <tr class="has-text-weight-bold">
        <td></td>
        <td class="has-text-right">Total</td>
        <td class="has-text-right">{$total_items_cost}€</td>
        <td></td>
    </tr>
</tbody>
</table>
<script>
    document.querySelectorAll('.action-btn').forEach(btn => {
        btn.onclick = async function() {
            try {
                const response = await fetch('item_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: btn.dataset.action,
                        item_id: btn.dataset.itemId
                    })
                });
                
                const data = await response.json();
                if (data.error) {
                    alert(data.error);
                    return;
                }
                
                // Reload the page to show updated status
                location.reload();
            } catch (error) {
                alert('Erro ao processar ação: ' + error.message);
            }
        };
    });
</script>
</div>
HTML;