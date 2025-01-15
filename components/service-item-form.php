<?php

return <<<HTML
    <div class="box">
        <h4 class="title is-5">Adicionar Item</h4>
        <div class="columns is-mobile is-multiline">
            <div class="column is-8-desktop is-12-mobile">
                <div class="field">
                    <label class="label">Descrição</label>
                    <div class="control">
                        <input class="input" type="text" id="new-description">
                    </div>
                </div>
            </div>
            <div class="column is-2-desktop is-6-mobile">
                <div class="field">
                    <label class="label">Preço</label>
                    <div class="control has-icons-left">
                        <input class="input" type="number" id="new-price" step="0.01" min="0">
                        <span class="icon is-small is-left">
                            <i class="fas fa-euro-sign"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="column is-2-desktop is-6-mobile">
                <div class="field">
                    <label class="label">&nbsp;</label>
                    <div class="control">
                        <button type="button" class="button is-primary is-fullwidth" onclick="addItem()">
                            <span class="icon">
                                <i class="fas fa-plus"></i>
                            </span>
                            <span>Adicionar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function addItem() {
        const description = document.getElementById('new-description').value.trim();
        const price = parseFloat(document.getElementById('new-price').value);
        
        if (!description) return alert('Por favor insira uma descrição');
        if (isNaN(price) || price < 0) return alert('Por favor insira um preço válido');
        
        const tbody = document.getElementById('items-list');
        const rowCount = tbody.children.length;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="hidden" name="items[description][]" value="\${description}">
                \${description}
            </td>
            <td>
                <input type="hidden" name="items[price][]" value="\${price}">
                \${price.toFixed(2)}€
            </td>
            <td>
                <button type="button" class="button is-small is-danger" onclick="this.closest('tr').remove(); updateTotal();">
                    <span class="icon">
                        <i class="fas fa-trash"></i>
                    </span>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
        updateTotal();
        
        document.getElementById('new-description').value = '';
        document.getElementById('new-price').value = '';
    }
    
    function updateTotal() {
        const prices = Array.from(document.getElementsByName('items[price][]')).map(input => parseFloat(input.value) || 0);
        const total = prices.reduce((sum, price) => sum + price, 0);
        document.getElementById('total-amount').textContent = total.toFixed(2) + '€';
    }
    </script>
HTML;
