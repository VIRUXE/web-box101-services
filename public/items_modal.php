<?php
// Get distinct descriptions for datalist
$descriptions = [];
$result = $db->query("SELECT DISTINCT description FROM vehicle_service_items WHERE description IS NOT NULL ORDER BY description");
if ($result) while ($row = $result->fetch_array(MYSQLI_NUM)) if ($row[0]) $descriptions[] = $row[0];

echo <<<HTML
    <div class="modal" id="itemModal">
        <div class="modal-background" onclick="closeItemModal()"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title" id="modalTitle">Adicionar Item</p>
                <button class="delete" aria-label="close" onclick="closeItemModal()"></button>
            </header>
            <section class="modal-card-body">
                <form id="itemForm" onsubmit="return handleItemSubmit(event)">
                    <input type="hidden" name="service_id" value="$service_id">
                    <input type="hidden" name="item_id" id="item_id" value="">
                    <input type="hidden" name="action" id="form_action" value="add_item">
                    
                    <div class="field">
                        <label class="label">Descrição</label>
                        <div class="control">
                            <input class="input" type="text" name="description" required list="descriptions">
                            <datalist id="descriptions">
HTML;
foreach ($descriptions as $description) echo "<option value=\"$description\">";
echo <<<HTML
                            </datalist>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">Preço (€)</label>
                        <div class="control">
                            <input class="input" type="number" name="price" step="0.01" required>
                        </div>
                    </div>
                </form>
            </section>
            <footer class="modal-card-foot">
                <button type="submit" form="itemForm" class="button is-primary">Guardar</button>
                <button class="button" onclick="closeItemModal()">Cancelar</button>
            </footer>
        </div>
    </div>

    <script>
    function openItemModal(isEdit = false) {
        document.getElementById('modalTitle').textContent = isEdit ? 'Editar Item' : 'Adicionar Item';
        document.getElementById('form_action').value = isEdit ? 'edit_item' : 'add_item';
        document.getElementById('itemModal').classList.add('is-active');
        if (!isEdit) document.getElementById('itemForm').reset();
    }

    function closeItemModal() {
        document.getElementById('itemModal').classList.remove('is-active');
        document.getElementById('item_id').value = '';
    }

    async function handleItemSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.error) throw new Error(data.error);
            
            closeItemModal();
            location.reload();
        } catch (error) {
            alert('Erro: ' + error.message);
        }
        return false;
    }

    async function editItem(itemId) {
        try {
            const formData = new FormData();
            formData.append('action', 'get_item');
            formData.append('item_id', itemId);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.error) throw new Error(data.error);
            
            const form = document.getElementById('itemForm');
            const item = data.item;
            
            form.description.value = item.description;
            form.price.value = item.price;
            form.item_id.value = item.id;
            
            openItemModal(true);
        } catch (error) {
            alert('Erro: ' + error.message);
        }
    }

    async function deleteItem(itemId) {
        if (!confirm('Tem certeza que deseja excluir este item?')) return;

        const formData = new FormData();
        formData.append('action', 'delete_item');
        formData.append('item_id', itemId);
        formData.append('service_id', $service_id);

        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.error) throw new Error(data.error);
            
            location.reload();
        } catch (error) {
            alert('Erro: ' + error.message);
        }
    }
    </script>
HTML;
