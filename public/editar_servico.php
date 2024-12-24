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
    $action = $_POST['action'];

    try {
        $db->begin_transaction();

        $stmt = $db->prepare("UPDATE vehicle_services SET client_id = ?, starting_date = ?, state = ? WHERE id = ?");
        $stmt->bind_param("issi", $client_id, $starting_date, $state, $id);
        $stmt->execute();

        // Delete existing items
        $db->query("DELETE FROM vehicle_service_items WHERE service_id = $id");
        
        if (isset($_POST['items'])) {
            $stmt = $db->prepare("INSERT INTO vehicle_service_items (service_id, description, price) VALUES (?, ?, ?)");
            
            foreach ($_POST['items']['description'] as $i => $desc) {
                $price = $_POST['items']['price'][$i];
                $stmt->bind_param("isd", $id, $desc, $price);
                $stmt->execute();
            }
        }

        $db->commit();
        header("Location: " . ($action === 'save_and_see' ? "servico.php?id=$id" : "veiculo.php?matricula={$_POST['matricula']}"));
    } catch (Exception $e) {
        $db->rollback();
        echo '<div class="notification is-danger">Erro: ' . $e->getMessage() . '</div>';
    }
}

if ($id) {
    $query = $db->query("
        SELECT vs.*, v.matricula 
        FROM vehicle_services vs 
        LEFT JOIN vehicles v ON vs.matricula = v.matricula 
        WHERE vs.id = $id
    ");

    if ($query->num_rows) {
        $service = $query->fetch_object();
        $items = $db->query("SELECT * FROM vehicle_service_items WHERE service_id = $id ORDER BY id ASC");

        $pendingSelected    = $service->state == 'PENDING' ? 'selected' : '';
        $proposalSelected   = $service->state == 'PROPOSAL' ? 'selected' : '';
        $inProgressSelected = $service->state == 'IN_PROGRESS' ? 'selected' : '';
        $completedSelected  = $service->state == 'COMPLETED' ? 'selected' : '';
        $cancelledSelected  = $service->state == 'CANCELLED' ? 'selected' : '';

        echo <<<HTML
        <h1 class="title">Editar Serviço #{$id}</h1>
        <p class="subtitle">Veículo: {$service->matricula}</p>

        <form method="post">
            <input type="hidden" name="id" value="$id">
            <input type="hidden" name="matricula" value="{$service->matricula}">
            <div class="columns is-multiline-mobile">
                <div class="column is-5">
                    <div class="field is-horizontal">
                        <label class="label" style="margin-right: 10px">Cliente</label>
                        <div class="control has-icons-left">
                            <input class="input" type="text" id="client-search" placeholder="Pesquisar cliente" autocomplete="off">
                            <input type="hidden" name="client_id" id="client-id" value="{$service->client_id}">
                            <span class="icon is-small is-left">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                        <div id="client-results" class="box" style="display:none; position:absolute; width:100%; z-index:100; max-height:200px; overflow-y:auto;"></div>
                    </div>
                </div>
                <div class="column is-4">
                    <div class="field is-horizontal">
                        <label class="label" style="margin-right: 10px">Data de Início</label>
                        <div class="control">
                            <input class="input" type="date" name="starting_date" value="{$service->starting_date}">
                        </div>
                    </div>
                </div>
                <div class="column is-3">
                    <div class="field is-horizontal">
                        <label class="label" style="margin-right: 10px">Estado</label>
                        <div class="control">
                            <div class="select">
                                <select name="state">
                                    <option value="PENDING" {$pendingSelected}>Pendente</option>
                                    <option value="PROPOSAL" {$proposalSelected}>Proposta</option>
                                    <option value="IN_PROGRESS" {$inProgressSelected}>Em Progresso</option>
                                    <option value="COMPLETED" {$completedSelected}>Concluído</option>
                                    <option value="CANCELLED" {$cancelledSelected}>Cancelado</option>
                                </select>
                            </div>
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
                        <button type="button" class="button is-small is-danger is-outlined" 
                                onclick="this.closest('tr').remove(); calculateTotal()">Remover</button>
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
                            <div class="columns is-mobile is-gapless">
                                <div class="column">
                                    <input class="input" type="number" id="new-price" step="0.01" min="0">
                                </div>
                                <div class="column is-narrow" style="padding-left:5px;padding-top:8px">
                                    €
                                </div>
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
// ...existing code from criar_servico.php...
</script>
