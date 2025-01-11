<?php
if ($service_id <= 0) return;

echo <<<HTML
<script>
function openPartModal() {
    document.getElementById('partForm').reset();
    document.getElementById('part_id').value = '';
    document.getElementById('form_action').value = 'add_part';
    document.getElementById('modalTitle').textContent = 'Adicionar Peça';
    document.getElementById('partModal').classList.add('is-active');
}

function closePartModal() {
    document.getElementById('partModal').classList.remove('is-active');
}

function editPart(partId) {
    fetch('parts_manager.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_part&part_id=' + partId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const part = data.part;
            const form = document.getElementById('partForm');
            form.part_id.value = part.id;
            form.description.value = part.description;
            form.quantity.value = part.quantity;
            form.customer_price.value = part.customer_price;
            form.supplier_price.value = part.supplier_price || '';
            form.supplier_discount.value = part.supplier_discount || '';
            form.origin.value = part.origin || '';
            form.supplier_paid.checked = part.supplier_paid === 1;
            document.getElementById('form_action').value = 'edit_part';
            document.getElementById('modalTitle').textContent = 'Editar Peça';
            document.getElementById('partModal').classList.add('is-active');
        } else alert(data.error);
    })
    .catch(error => alert('Error loading part details'));
}

function deletePart(partId) {
    if (!confirm('Are you sure you want to delete this part?')) return;

    const formData = new FormData();
    formData.append('action', 'delete_part');
    formData.append('part_id', partId);
    formData.append('service_id', document.querySelector('input[name="service_id"]').value);

    fetch('parts_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) location.reload();
        else alert(data.error);
    })
    .catch(error => alert('Error deleting part'));
}

function handlePartSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch('parts_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closePartModal();
            location.reload();
        } else alert(data.error);
    })
    .catch(error => alert('Error saving part'));
    return false;
}
</script>
HTML;

$stmt = $db->prepare("SELECT vsp.*, CONCAT(u.first_name, ' ', u.last_name) as added_by_name FROM vehicle_service_parts vsp LEFT JOIN users u ON vsp.added_by = u.id WHERE vsp.service_id = ? ORDER BY vsp.id DESC");
if (!$stmt) die("Database error: " . $db->error);

$stmt->bind_param('i', $service_id);
if (!$stmt->execute()) die("Query failed: " . $stmt->error);

$result = $stmt->get_result();
$total_parts_cost = 0;
$total_supplier_cost = 0;

echo <<<HTML
<hr>
<div class="is-flex is-justify-content-space-between is-align-items-center">
    <h2 class="title is-4 mb-0">Peças</h2>
    <button class="button is-primary is-small" onclick="openPartModal()"><span class="icon"><i class="fas fa-plus"></i></span><span>Adicionar Peça</span></button>
</div>

<div class="table-container mt-4">
    <table class="table is-fullwidth is-striped is-hoverable">
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Quantidade</th>
                <th>Preço do Cliente</th>
                <th>Preço do Fornecedor</th>
                <th>Desconto</th>
                <th>Origem</th>
                <th>Pago</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
HTML;

while ($part = $result->fetch_object()) {
    $total_parts_cost += floatval($part->customer_price) * $part->quantity;
    if ($part->supplier_price) $total_supplier_cost += floatval($part->supplier_price) * $part->quantity;

    $supplier_price = $part->supplier_price ? number_format(floatval($part->supplier_price), 2, ',', '.') : '-';
    $customer_price = number_format(floatval($part->customer_price), 2, ',', '.');
    $supplier_discount = $part->supplier_discount ? "{$part->supplier_discount}%" : '-';
    $origin = $part->origin ?: '-';
    $paid_status = $part->supplier_paid ? '<span class="tag is-success">Sim</span>' : '<span class="tag is-warning">Não</span>';

    echo <<<HTML
        <tr>
            <td>
                <div>
                    <p class="has-text-weight-medium mb-1">{$part->description}</p>
                    <p class="has-text-grey is-size-7">Adicionado por {$part->added_by_name}</p>
                </div>
            </td>
            <td>{$part->quantity}</td>
            <td>€ {$customer_price}</td>
            <td>€ {$supplier_price}</td>
            <td>{$supplier_discount}</td>
            <td>{$origin}</td>
            <td>{$paid_status}</td>
            <td>
                <div class="buttons are-small">
                    <button class="button is-info" onclick="editPart({$part->id})"><span class="icon"><i class="fas fa-edit"></i></span></button>
                    <button class="button is-danger" onclick="deletePart({$part->id})"><span class="icon"><i class="fas fa-trash"></i></span></button>
                </div>
            </td>
        </tr>
    HTML;
}

$total_parts_cost = number_format($total_parts_cost, 2, ',', '.');
$total_supplier_cost = number_format($total_supplier_cost, 2, ',', '.');

echo <<<HTML
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="has-text-right">Total:</th>
                <td>€ {$total_parts_cost}</td>
                <td>€ {$total_supplier_cost}</td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>
</div>
HTML;

include 'parts_modal.php';
$stmt->close();
