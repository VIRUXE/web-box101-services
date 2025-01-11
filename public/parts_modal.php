<?php
// Get distinct origins and descriptions for datalists
$origins = [];
$descriptions = [];
$result = $db->query("SELECT DISTINCT origin FROM vehicle_service_parts WHERE origin IS NOT NULL ORDER BY origin");
if ($result) while ($row = $result->fetch_array(MYSQLI_NUM)) if ($row[0]) $origins[] = $row[0];

$result = $db->query("SELECT DISTINCT description FROM vehicle_service_parts WHERE description IS NOT NULL ORDER BY description");
if ($result) while ($row = $result->fetch_array(MYSQLI_NUM)) if ($row[0]) $descriptions[] = $row[0];

echo <<<HTML
    <div class="modal" id="partModal">
        <div class="modal-background" onclick="closePartModal()"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title" id="modalTitle">Adicionar Peça</p>
                <button class="delete" aria-label="close" onclick="closePartModal()"></button>
            </header>
            <section class="modal-card-body">
                <form id="partForm" onsubmit="return handlePartSubmit(event)">
                    <input type="hidden" name="service_id" value="$service_id">
                    <input type="hidden" name="part_id" id="part_id" value="">
                    <input type="hidden" name="action" id="form_action" value="add_part">
                    
                    <div class="field">
                        <label class="label">Descrição</label>
                        <div class="control">
                            <input class="input" type="text" name="description" required list="descriptions">
                            <datalist id="descriptions">
HTML;
foreach ($descriptions as $description) {
    echo <<<HTML
                                <option value="$description">
HTML;
}
echo <<<HTML
                            </datalist>
                        </div>
                    </div>

                    <div class="columns is-mobile">
                        <div class="column">
                            <div class="field">
                                <label class="label">Quantidade</label>
                                <div class="control">
                                    <input class="input" type="number" name="quantity" value="1" min="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="column">
                            <div class="field">
                                <label class="label">Preço Cliente (€)</label>
                                <div class="control">
                                    <input class="input" type="number" name="customer_price" step="0.01" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box">
                        <h6 class="title is-6">Informações do Fornecedor</h6>
                        <div class="columns is-mobile">
                            <div class="column">
                                <div class="field">
                                    <label class="label">Preço Forn. (€)</label>
                                    <div class="control">
                                        <input class="input" type="number" name="supplier_price" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="column">
                                <div class="field">
                                    <label class="label">Desconto (%)</label>
                                    <div class="control">
                                        <input class="input" type="number" name="supplier_discount" min="0" max="100">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="columns is-mobile">
                            <div class="column">
                                <div class="field">
                                    <label class="label">Origem</label>
                                    <div class="control">
                                        <input class="input" type="text" name="origin" list="origins">
                                        <datalist id="origins">
HTML;
foreach ($origins as $origin) {
    echo <<<HTML
                                            <option value="$origin">
HTML;
}
echo <<<HTML
                                        </datalist>
                                    </div>
                                </div>
                            </div>
                            <div class="column">
                                <div class="field">
                                    <div class="control" style="padding-top: 2rem;">
                                        <label class="checkbox">
                                            <input type="checkbox" name="supplier_paid" id="supplier_paid">
                                            Fornecedor Pago
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </section>
            <footer class="modal-card-foot">
                <button type="submit" form="partForm" class="button is-primary">Guardar</button>
                <button class="button" onclick="closePartModal()">Cancelar</button>
            </footer>
        </div>
    </div>
HTML;
