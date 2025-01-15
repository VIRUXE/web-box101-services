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

    $state                 = $service->getState();
    $state_label           = $state->label();
    $state_color           = $state->color();
    $total_cost            = $parts->customer_total + $labor->total;
    $paid_amount           = $service->getPaidAmount() ?? 0;
    $minimum_to_break_even = $parts->supplier_total + $labor->total;
    $tip                   = max(0, $paid_amount - $total_cost);
    $profit                = $paid_amount - $minimum_to_break_even;
    $profit_class          = match(true) {
        $paid_amount < $minimum_to_break_even => 'has-text-danger',
        $paid_amount < $total_cost => 'has-text-warning',
        default => 'has-text-success'
    };
    $profit_string = "{$profit}€";
    if ($tip > 0) $profit_string .= " (+{$tip}€ gorjeta)";

    $starting_date     = $service->getStartingDate() ?? 'N/D';
    $ending_date       = $service->getEndingDate() ?? 'N/D';
    $starting_odometer = $service->getStartingOdometer() ?: 'N/D';
    $finished_odometer = $service->getFinishedOdometer() ?: 'N/D';

    echo <<<HTML
        <div class="columns is-vcentered mb-4">
            <div class="column">
                <h1 class="title is-3 mb-0">Serviço <span class="has-text-grey-dark">#{$service->getId()}</span></h1>
                <p class="subtitle is-6 is-italic has-text-grey">Criado em {$service->getCreatedAt()}</p>
                <span class="tag is-large has-text-weight-bold"><a href="veiculo.php?matricula={$service->getMatricula()}">{$vehicle->plate}</a></span>
                <span class="tag {$state_color} is-medium">{$state_label}</span>
            </div>
            <div class="column is-narrow is-align-items-end">
                <div class="buttons">
                    <button class="button" onclick="toggleSupplierCosts()" id="toggleCostsBtn">
                        <span class="icon"><i class="fas fa-eye-slash"></i></span>
                    </button>
                    <button class="button" onclick="deleteService({$service->getId()})">
                        <span class="icon"><i class="fas fa-trash"></i></span>
                    </button>
                    <button id="shareBtn" class="button">
                        <span class="icon"><i class="fas fa-share"></i></span>
                        <span>Partilhar</span>
                    </button>
                    <a href="imprimir_servico.php?id={$service->getId()}" class="button is-info" target="_blank">
                        <span class="icon"><i class="fas fa-print"></i></span>
                        <span>Imprimir</span>
                    </a>
                    <a href="editar_servico.php?id={$service->getId()}" class="button is-primary">
                        <span class="icon"><i class="fas fa-edit"></i></span>
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
                        <p><strong>Total em Peças</strong> <span class="tag"><span class="has-text-weight-bold mr-1">Cliente:</span> {$parts->customer_total}€</span> <span class="tag supplier-cost"><span class="has-text-weight-bold mr-1">Fornecedor:</span> {$parts->supplier_total}€</span></p>
                        <p><strong>Total em Mão de Obra:</strong> {$labor->total}€</p>
                        <p><strong>Valor Pago:</strong> {$paid_amount}€ ({$total_cost}€)</p>
                        <p class="has-text-weight-bold {$profit_class}"><strong>Lucro:</strong> {$profit_string}</p>
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

<script>
let supplierCostsVisible = true;
function toggleSupplierCosts() {
    supplierCostsVisible = !supplierCostsVisible;
    const icon = document.getElementById('toggleCostsBtn').querySelector('.fas');

    icon.classList.toggle('fa-eye-slash');
    icon.classList.toggle('fa-eye');
    
    document.querySelectorAll('.supplier-cost').forEach(el => el.style.display = supplierCostsVisible ? '' : 'none');
}

document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('.supplier-cost').forEach(el => el.style.display = ''));

const formatCurrency = value => new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(value);

async function shareService() {
    const serviceText = `🚗 *Serviço #${<?= $service->getId(); ?>}*\n
📅 <?= $service->getCreatedAt(); ?>\n
🚘 *Veículo*
Marca: <?= $vehicle->brand; ?>\r
Modelo: <?= $vehicle->model; ?>\r
Matrícula: <?= $vehicle->plate; ?>\r
Cor: <?= $vehicle->colour; ?>\n
💰 *Valores*
Peças: ${formatCurrency(<?= $parts->customer_total; ?>)}
Mão de obra: ${formatCurrency(<?= $labor->total; ?>)}
Total: ${formatCurrency(<?= $parts->customer_total + $labor->total; ?>)}\n
Estado: <?= $service->getState()->label(); ?>`;

    try {
        await navigator.clipboard.writeText(serviceText);
        const originalHtml = this.innerHTML;
        this.innerHTML = '<span class="icon"><i class="fas fa-check"></i></span><span>Copiado!</span>';
        this.classList.add('is-success');
        setTimeout(() => {
            this.innerHTML = originalHtml;
            this.classList.remove('is-success');
        }, 2000);
    } catch (err) {
        alert('Erro ao copiar para área de transferência: ' + err);
    }
}
document.getElementById('shareBtn').addEventListener('click', shareService);
</script>
</body>
</html>