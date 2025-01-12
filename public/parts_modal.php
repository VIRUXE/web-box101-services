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
                        <div class="column is-half">
                            <div class="field">
                                <label class="label">Quantidade</label>
                                <div class="control">
                                    <input class="input" type="number" name="quantity" value="1" min="1" required>
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
                                    <label class="label">Origem/Fornecedor</label>
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

                    <div class="columns is-mobile">
                        <div class="column">
                            <div class="field">
                                <label class="label">Preço Cliente (€)</label>
                                <div class="control">
                                    <input class="input" type="number" name="customer_price" step="0.01" required>
                                </div>
                                <p class="help" id="markup_info"></p>
                            </div>
                        </div>
                        <div class="column">
                            <div class="field">
                                <label class="label">Quanto queres lixar o cliente?</label>
                                <div class="control">
                                    <input class="slider is-fullwidth" step="1" min="0" max="500" value="30" type="range" id="markup_slider" disabled>
                                </div>
                                <p class="help has-text-centered"><span id="slider_value">30</span>%</p>
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

?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const supplierPriceInput = document.querySelector('input[name="supplier_price"]');
    const customerPriceInput = document.querySelector('input[name="customer_price"]');
    const markupSlider = document.getElementById('markup_slider');
    const sliderValue = document.getElementById('slider_value');
    const markupInfo = document.getElementById('markup_info');

    function calculateMarkup() {
        const supplierPrice = parseFloat(supplierPriceInput.value) || 0;
        const customerPrice = parseFloat(customerPriceInput.value) || 0;
        
        if (supplierPrice > 0 && customerPrice > 0) {
            const markup = ((customerPrice - supplierPrice) / supplierPrice * 100).toFixed(1);
            markupInfo.textContent = `Margem atual: ${markup}%`;
            markupInfo.classList.remove('is-danger');
        } else markupInfo.textContent = '';
    }

    function updateCustomerPrice() {
        const supplierPrice = parseFloat(supplierPriceInput.value) || 0;
        const markup = parseFloat(markupSlider.value) || 0;
        
        if (supplierPrice > 0) {
            const newPrice = (supplierPrice * (1 + markup/100)).toFixed(2);
            customerPriceInput.value = newPrice;
            calculateMarkup();
        }
    }

    supplierPriceInput.addEventListener('input', function() {
        markupSlider.disabled = !this.value;
        if (this.value) updateCustomerPrice();
        else {
            markupInfo.textContent = '';
            customerPriceInput.value = '';
        }
    });

    markupSlider.addEventListener('input', function() {
        sliderValue.textContent = this.value;
        updateCustomerPrice();
    });

    customerPriceInput.addEventListener('input', calculateMarkup);
});
</script>
