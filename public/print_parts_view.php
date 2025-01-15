<?php
$query = $db->query("SELECT description, quantity, customer_price FROM vehicle_service_parts WHERE service_id = {$service_id} AND deleted = 0 ORDER BY id DESC");

if ($query->num_rows > 0) {
    echo <<<HTML
    <section>
        <h2>Peças</h2>
        <table>
            <thead>
                <tr>
                    <th scope="col">Descrição</th>
                    <th scope="col" aria-label="valor">Qtd.</th>
                    <th scope="col" aria-label="valor">Preço Un.</th>
                    <th scope="col" aria-label="valor">Total</th>
                </tr>
            </thead>
            <tbody>
    HTML;

    while ($part = $query->fetch_object()) {
        $unit_price = number_format($part->customer_price, 2, ',', '.');
        $total = number_format($part->customer_price * $part->quantity, 2, ',', '.');
        echo <<<HTML
            <tr>
                <td>{$part->description}</td>
                <td aria-label="valor">{$part->quantity}</td>
                <td aria-label="valor">{$unit_price}€</td>
                <td aria-label="valor">{$total}€</td>
            </tr>
        HTML;
    }

    echo <<<HTML
            </tbody>
        </table>
    </section>
    HTML;
}
