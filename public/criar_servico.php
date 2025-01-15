<?php
require_once '../User.class.php';
require_once '../Service.class.php';
require_once '../ServiceState.enum.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

if (!$logged_user->isAdmin()) header('Location: index.php');

require '../database.php';
require 'header.php';

echo <<<HTML
<section class="section">
	<div class="container">
HTML;
		
if ($_POST) {
	$matricula = $_POST['matricula'];
	$starting_date = !empty($_POST['starting_date']) ? $_POST['starting_date'] : NULL;
	$state = ServiceState::from($_POST['state']);
	$client_id = !empty($_POST['client_id']) ? (int)$_POST['client_id'] : NULL;
	$action = $_POST['action'];
	$starting_odometer = !empty($_POST['starting_odometer']) ? (int)$_POST['starting_odometer'] : NULL;

	$service = Service::create(
		$matricula,
		$logged_user->id,
		$state,
		$client_id,
		$starting_date,
		$starting_odometer
	);

	if ($service) {
		if (isset($_POST['items'])) {
			foreach ($_POST['items']['description'] as $i => $desc) {
				$price = $_POST['items']['price'][$i];
				$service->addItem($desc, $price);
			}
		}
		header("Location: " . ($action === 'create_and_see' ? "servico.php?id={$service->getId()}" : "veiculo.php?matricula=$matricula"));
	} else {
		echo '<div class="notification is-danger">Erro ao criar serviço</div>';
	}
}

$matricula = $_GET['matricula'] ?? NULL;
		
if ($matricula) {
	require '../database.php';
	$query = $db->query("SELECT * FROM vehicles WHERE matricula = '$matricula'");

	if ($query->num_rows) {
		require '../Vehicle.class.php';

		$vehicle = new Vehicle($query->fetch_assoc());

		// Get last known odometer reading
		$odometer_query = $db->query("
			SELECT NULLIF(COALESCE(
				(SELECT starting_odometer 
				FROM vehicle_services 
				WHERE matricula = '$matricula' 
				AND starting_odometer IS NOT NULL 
				AND starting_odometer > 0
				ORDER BY starting_date DESC, id DESC 
				LIMIT 1),
				(SELECT NULLIF(odometer, 0) 
				FROM vehicles 
				WHERE matricula = '$matricula')
			), 0) as last_odometer
		");
		$last_odometer = $odometer_query->fetch_assoc()['last_odometer'];
		
		// Prepare odometer HTML parts for heredoc
		$odometer_value = $last_odometer !== null ? " value=\"$last_odometer\"" : '';
		$odometer_help = '';
		if ($last_odometer !== null) {
			$odometer_source = $db->query("
				SELECT EXISTS(
					SELECT 1 FROM vehicle_services 
					WHERE matricula = '$matricula' 
					AND starting_odometer = $last_odometer
				) as from_service
			")->fetch_assoc()['from_service'] ? 'último serviço' : 'registo do veículo';
			$odometer_help = "<p class=\"help\">Último valor conhecido (do $odometer_source): $last_odometer km</p>";
		}

		echo <<<HTML
		<h1 class="title">Criar Serviço</h1>
		<p class="subtitle">$vehicle</p>

		<form method="post">
			<input type="hidden" name="matricula" value="$matricula">
			<div class="columns is-multiline-mobile">
				<div class="column is-3">
					<div class="field is-horizontal">
						<label class="label" style="margin-right: 10px">Cliente</label>
						<div class="control has-icons-left">
							<input class="input" type="text" id="client-search" placeholder="Pesquisar cliente por nome, email ou telefone" autocomplete="off">
							<input type="hidden" name="client_id" id="client-id">
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
							<input class="input" type="date" name="starting_date" id="starting_date">
						</div>
					</div>
				</div>
			</div>
			<div class="columns is-multiline-mobile">
				<div class="column is-3">
					<div class="field is-horizontal">
						<label class="label" style="margin-right: 10px">Km Inicial</label>
						<div class="control">
							<input class="input" type="number" name="starting_odometer" min="0"$odometer_value>
							$odometer_help
						</div>
					</div>
				</div>
				<div class="column is-3">
					<div class="field is-horizontal">
						<label class="label" style="margin-right: 10px">Estado</label>
						<div class="control">
							<div class="select">
								<select name="state">
									<option value="PENDING">Pendente</option>
									<option value="AWAITING_APPROVAL">A Aguardar Aprovação</option>
									<option value="APPROVED">Aprovado</option>
									<option value="IN_PROGRESS">Em Execução</option>
									<option value="COMPLETED">Concluído</option>
									<option value="CANCELLED">Cancelado</option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="box collapsible">
				<h4 class="title is-5">Legenda dos Estados</h4>
				<div class="content" style="display: none;">
					<p><strong>Pendente:</strong> O serviço está pendente de início.</p>
					<p><strong>Orçamento:</strong> O serviço está em fase de orçamento.</p>
					<p><strong>A Aguardar Aprovação:</strong> O serviço está aguardando aprovação do cliente.</p>
					<p><strong>Aprovado:</strong> O serviço foi aprovado e está pronto para execução.</p>
					<p><strong>Em Execução:</strong> O serviço está em execução.</p>
					<p><strong>Concluído:</strong> O serviço foi concluído.</p>
					<p><strong>Cancelado:</strong> O serviço foi cancelado.</p>
				</div>
			</div>
			<h3 class="title is-4 mt-6">Itens do Serviço</h3>
			<p>Adicione os itens que compõem o serviço</p>
			
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
						<tbody id="items-list"></tbody>
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
HTML;

echo require '../components/service-item-form.php';

echo <<<HTML
			<div class="field is-grouped is-grouped-multiline mt-5">
				<div class="control is-expanded">
					<input type="hidden" name="action" id="submitAction" value="">
					<div class="buttons">
						<button type="submit" class="button is-success is-fullwidth-mobile" onclick="setAction('create_and_see')">
							<span class="icon">
								<i class="fas fa-eye"></i>
							</span>
							<span>Criar e Visualizar</span>
						</button>
						<button type="submit" class="button is-info is-fullwidth-mobile" onclick="setAction('create')">
							<span class="icon">
								<i class="fas fa-save"></i>
							</span>
							<span>Criar</span>
						</button>
						<a href="veiculo.php?matricula=$matricula" class="button is-fullwidth-mobile">
							<span class="icon">
								<i class="fas fa-times"></i>
							</span>
							<span>Cancelar</span>
						</a>
					</div>
				</div>
			</div>
		</form>
		HTML;
	} else echo '<div class="notification is-danger">Veículo não encontrado</div>';
} else echo '<div class="notification is-danger">Matrícula não especificada</div>';

echo <<<HTML
	</div>
</section>
HTML;

require 'footer.php';
?>
<script>
const collapsible = document.querySelector('.collapsible');
const content = collapsible.querySelector('.content');
collapsible.querySelector('.title').addEventListener('click', () => content.style.display = content.style.display === 'none' ? '' : 'none');

function calculateTotal() {
    const prices = Array.from(document.getElementsByName('items[price][]')).map(input => Number(input.value));
    const total = prices.reduce((sum, price) => sum + price, 0);
    document.getElementById('total-amount').textContent = total.toFixed(2) + '€';
}

function addItem() {
	const description = document.getElementById('new-description').value;
	const price = document.getElementById('new-price').value;
	
	if (!description) return;

	const tr = document.createElement('tr');
	tr.innerHTML = `
		 <td style="width: 70%">
			${description}
			<input type="hidden" name="items[description][]" value="${description}">
		</td>
		<td style="width: 15%; white-space: nowrap">
			${Number(price).toFixed(2)}€
			<input type="hidden" name="items[price][]" value="${price}">
		</td>
		<td style="width: 15%">
			<button type="button" class="button is-small is-danger is-outlined" onclick="this.closest('tr').remove(); calculateTotal()"><span class="icon"><i class="fas fa-trash"></i></span></button>
		</td>
	`;
	
	document.getElementById('items-list').appendChild(tr);
	document.getElementById('new-description').value = '';
	document.getElementById('new-price').value = '';
	calculateTotal();
}

// Set default date
const now = new Date();
const year = now.getFullYear();
const month = String(now.getMonth() + 1).padStart(2, '0');
const day = String(now.getDate()).padStart(2, '0');
document.getElementById('starting_date').value = `${year}-${month}-${day}`;

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

const setAction = action => document.getElementById('submitAction').value = action
</script>