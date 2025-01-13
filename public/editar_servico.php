<?php
include_once '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if (!$logged_user->isAdmin()) header('Location: index.php');

include '../database.php';
include 'header.php';

echo <<<HTML
<section class="section">
    <div class="container">
HTML;

$id = isset($_GET['id']) ? (int)$_GET['id'] : NULL;

if ($_POST) {
    $id = (int)$_POST['id'];
    $starting_date = !empty($_POST['starting_date']) ? $_POST['starting_date'] : NULL;
    $state = $_POST['state'];
    $client_id = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : NULL;
    $paid_amount = !empty($_POST['paid_amount']) ? (float)$_POST['paid_amount'] : NULL;
    $action = $_POST['action'];
    $error = null;

    try {
        $db->begin_transaction();

        // Validate state change if items are in progress
        if (in_array($state, ['PENDING', 'AWAITING_APPROVAL'])) {
            $items_check = $db->prepare("SELECT COUNT(*) as count FROM vehicle_service_items WHERE service_id = ? AND status IN ('STARTED', 'PAUSED')");
            $items_check->bind_param("i", $id);
            $items_check->execute();
            $active_items = $items_check->get_result()->fetch_object()->count;
            
            if ($active_items > 0) {
                $error = "Não é possível alterar o estado para Pendente ou A Aguardar Aprovação enquanto existirem itens em curso ou em pausa.";
                throw new Exception($error);
            }
        }

        // Get current service data
        $current = $db->prepare("SELECT client_id, starting_date, state, paid_amount FROM vehicle_services WHERE id = ?");
        $current->bind_param("i", $id);
        $current->execute();
        $current_data = $current->get_result()->fetch_object();

        // Compare and build update query dynamically
        $updates = [];
        $types = "";
        $params = [];

        if ($current_data->client_id != $client_id) {
            $updates[] = "client_id = ?";
            $types .= "i";
            $params[] = $client_id;
        }
        if ($current_data->starting_date != $starting_date) {
            $updates[] = "starting_date = ?";
            $types .= "s";
            $params[] = $starting_date;
        }
        if ($current_data->state != $state) {
            $updates[] = "state = ?";
            $types .= "s";
            $params[] = $state;
        }
        if ($current_data->paid_amount != $paid_amount) {
            $updates[] = "paid_amount = ?";
            $types .= "d";
            $params[] = $paid_amount;
        }

        // Only update if there are changes
        if (!empty($updates)) {
            $updates[] = "updated_by = ?";
            $updates[] = "updated_at = ?";
            $types .= "is";
            $params[] = $logged_user->id;
            $params[] = date('Y-m-d H:i:s');
            
            $sql = "UPDATE vehicle_services SET " . implode(", ", $updates) . " WHERE id = ?";
            $types .= "i";
            $params[] = $id;
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
        }

        // Handle service items - first get current items
        $current_items = [];
        $items_result = $db->query("SELECT id, description, price FROM vehicle_service_items WHERE service_id = $id");
        while ($row = $items_result->fetch_object()) {
            $current_items[$row->description] = ['id' => $row->id, 'price' => $row->price];
        }

        if (isset($_POST['items'])) {
            $stmt = $db->prepare("INSERT INTO vehicle_service_items (service_id, description, price, created_by) VALUES (?, ?, ?, ?)");
            
            // Track which items we've processed
            $processed_items = [];
            
            foreach ($_POST['items']['description'] as $i => $desc) {
                $price = $_POST['items']['price'][$i];
                $processed_items[] = $desc;
                
                // Only insert if it's a new item
                if (!isset($current_items[$desc])) {
                    $stmt->bind_param("isdi", $id, $desc, $price, $logged_user->id);
                    $stmt->execute();
                } elseif ($current_items[$desc]['price'] != $price) {
                    // Update if price changed
                    $update_stmt = $db->prepare("UPDATE vehicle_service_items SET price = ?, updated_by = ?, updated_at = ? WHERE id = ?");
                    $update_stmt->bind_param("diis", $price, $logged_user->id, date('Y-m-d H:i:s'), $current_items[$desc]['id']);
                    $update_stmt->execute();
                }
            }
            
            // Remove items that are no longer in the form
            $items_to_remove = array_diff(array_keys($current_items), $processed_items);
            if (!empty($items_to_remove)) {
                $items_list = "'" . implode("','", array_map([$db, 'real_escape_string'], $items_to_remove)) . "'";
                $db->query("DELETE FROM vehicle_service_items WHERE service_id = $id AND description IN ($items_list)");
            }
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
        $error = $e->getMessage();
    }
    
    if ($error) {
        echo <<<HTML
        <div class="notification is-danger">
            <button class="delete" onclick="this.parentElement.style.display='none';"></button>
            $error
        </div>
HTML;
    } else {
        header("Location: " . ($action === 'save_and_see' ? "servico.php?id=$id" : "veiculo.php?matricula={$_POST['matricula']}"));
        exit;
    }
}

if ($id) {
    $query = $db->query("
        SELECT service.*, v.matricula, 
               CONCAT(u.first_name, ' ', u.last_name) as client_name 
        FROM vehicle_services service 
        LEFT JOIN vehicles v ON service.matricula = v.matricula 
        LEFT JOIN users u ON service.client_id = u.id
        WHERE service.id = $id
    ");

    if ($query->num_rows) {
        $service = $query->fetch_object();
        $items   = $db->query("SELECT * FROM vehicle_service_items WHERE service_id = $id ORDER BY id ASC");

        $pendingSelected          = $service->state == 'PENDING' ? 'selected' : '';
        $awaitingApprovalSelected = $service->state == 'AWAITING_APPROVAL' ? 'selected' : '';
        $approvedSelected         = $service->state == 'APPROVED' ? 'selected' : '';
        $inProgressSelected       = $service->state == 'IN_PROGRESS' ? 'selected' : '';
        $completedSelected        = $service->state == 'COMPLETED' ? 'selected' : '';
        $cancelledSelected        = $service->state == 'CANCELLED' ? 'selected' : '';

        echo <<<HTML
        <h1 class="title">Editar Serviço #{$id}</h1>
        <p class="subtitle">Veículo: {$service->matricula}</p>

        <form method="post">
            <input type="hidden" name="id" value="$id">
            <input type="hidden" name="matricula" value="{$service->matricula}">
            <div class="columns is-multiline-mobile">
                <div class="column">
                    <div class="field is-horizontal">
                        <label class="label mr-2">Cliente</label>
                        <div class="control has-icons-left">
                            <input class="input" type="text" id="client-search" placeholder="Pesquisar cliente" autocomplete="off" value="{$service->client_name}">
                            <input type="hidden" name="client_id" id="client-id" value="{$service->client_id}">
                            <span class="icon is-small is-left">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                        <div id="client-results" class="box" style="display:none; position:absolute; width:100%; z-index:100; max-height:200px; overflow-y:auto;"></div>
                    </div>
                </div>
                <div class="column is-narrow">
                    <div class="field is-horizontal">
                        <label class="label mr-2">Data de Início</label>
                        <div class="control has-icons-left">
                            <input class="input" type="datetime-local" name="starting_date" value="{$service->starting_date}">
                            <span class="icon is-small is-left">
                                <i class="fas fa-calendar"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="column is-narrow">
                    <div class="field is-horizontal">
                        <label class="label mr-2">Estado</label>
                        <div class="control">
                            <div class="select">
                                <select name="state">
                                    <option value="PENDING" {$pendingSelected}>Pendente</option>
                                    <option value="AWAITING_APPROVAL" {$awaitingApprovalSelected}>Aguardar Aprovação</option>
                                    <option value="APPROVED" {$approvedSelected}>Aprovado</option>
                                    <option value="CANCELLED" {$cancelledSelected}>Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="column is-narrow is-2">
                    <div class="field is-horizontal">
                        <label class="label mr-2">Valor Pago</label>
                        <div class="control has-icons-left">
                            <span class="icon is-small is-left"><i class="fas fa-euro-sign"></i></span>
                            <input class="input" type="number" name="paid_amount" value="{$service->paid_amount}" placeholder="Digite o valor pago" required>
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="title is-4 mt-6">Itens do Serviço</h3>
            <div class="columns my-2">
                <div class="column">
                    <table class="table is-fullwidth">
                        <thead>
                            <tr>
                                <th style="width: 70%">Descrição</th>
                                <th style="width: 15%">Preço</th>
                                <th style="width: 15%">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="items-list">
HTML;

        while ($item = $items->fetch_object()) {
            echo <<<HTML
                <tr>
                    <td style="width: 70%">
                        {$item->description}
                        <input type="hidden" name="items[description][]" value="{$item->description}">
                    </td>
                    <td style="width: 15%; white-space: nowrap">
                        {$item->price}€
                        <input type="hidden" name="items[price][]" value="{$item->price}">
                    </td>
                    <td style="width: 15%">
                        <button type="button" class="button is-small is-danger is-outlined" onclick="this.closest('tr').remove(); calculateTotal()">Remover</button>
                    </td>
                </tr>
HTML;
        }

        echo <<<HTML
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="has-text-right"><strong>Total:</strong></td>
                                <td id="total-amount">0.00€</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="box">
                <h4 class="title is-5">Adicionar Item</h4>
                <div class="columns">
                    <div class="column is-8">
                        <div class="field">
                            <label class="label">Descrição</label>
                            <div class="control">
                                <input class="input" type="text" id="new-description">
                            </div>
                        </div>
                    </div>
                    <div class="column is-2">
                        <div class="field">
                            <label class="label">Preço</label>
                            <div class="control has-icons-left">
                                <span class="icon is-small is-left"><i class="fas fa-euro-sign"></i></span>
                                <input class="input" type="number" id="new-price" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="column is-2">
                        <div class="field" style="margin-top: 1.9rem">
                            <button type="button" class="button is-info is-fullwidth" onclick="addItem()">Adicionar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="field mt-6">
                <div class="control">
                    <input type="hidden" name="action" id="submitAction" value="">
                    <button type="submit" class="button is-success" onclick="setAction('save_and_see')">Guardar e Ver</button>
                    <button type="submit" class="button is-success" onclick="setAction('save')">Guardar</button>
                    <a href="servico.php?id={$id}" class="button is-text">Voltar</a>
                </div>
            </div>
        </form>
HTML;
    } else echo '<div class="notification is-danger">Serviço não encontrado</div>';
} else echo '<div class="notification is-danger">ID do serviço não especificado</div>';

echo <<<HTML
    </div>
</section>
HTML;

include 'footer.php';
?>

<script>
// Client search functionality
const clientSearch = document.getElementById('client-search');
const clientResults = document.getElementById('client-results');
const clientId = document.getElementById('client-id');

let searchTimeout;

clientSearch.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value;
    
    if (query.length < 2) {
        clientResults.style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch(`api/search_clients.php?pesquisa=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                clientResults.innerHTML = '';
                
                if (data.clients && data.clients.length > 0) {
                    data.clients.forEach(client => {
                        const div = document.createElement('div');
                        div.className = 'p-2 hover:bg-gray-100 cursor-pointer';
                        div.innerHTML = `${client.first_name} ${client.last_name || ''} (${client.email || client.phone})`;
                        div.onclick = () => {
                            clientSearch.value = `${client.first_name} ${client.last_name || ''}`;
                            clientId.value = client.id;
                            clientResults.style.display = 'none';
                        };
                        clientResults.appendChild(div);
                    });
                    clientResults.style.display = 'block';
                } else {
                    clientResults.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                clientResults.style.display = 'none';
            });
    }, 300);
});

document.addEventListener('click', e => !clientSearch.contains(e.target) && !clientResults.contains(e.target) && (clientResults.style.display = 'none'));

// Service items functionality
const itemsList = document.getElementById('items-list');
const newDescription = document.getElementById('new-description');
const newPrice = document.getElementById('new-price');
const totalAmount = document.getElementById('total-amount');

function addItem() {
    if (!newDescription.value || !newPrice.value) return;

    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="hidden" name="items[description][]" value="${newDescription.value}">
            ${newDescription.value}
        </td>
        <td>
            <input type="hidden" name="items[price][]" value="${newPrice.value}">
            ${parseFloat(newPrice.value).toFixed(2)}€
        </td>
        <td>
            <button type="button" class="button is-small is-danger" onclick="this.closest('tr').remove(); updateTotal();">
                <span class="icon"><i class="fas fa-trash"></i></span>
            </button>
        </td>
    `;
    
    itemsList.appendChild(row);
    updateTotal();
    
    newDescription.value = '';
    newPrice.value = '';
}

function updateTotal() {
    const prices = Array.from(document.getElementsByName('items[price][]')).map(input => parseFloat(input.value) || 0);
    const total = prices.reduce((sum, price) => sum + price, 0);
    totalAmount.textContent = total.toFixed(2) + '€';
}

function setAction(action) {
    document.getElementById('submitAction').value = action;
}
</script>
