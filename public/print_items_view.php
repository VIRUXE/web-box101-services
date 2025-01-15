<?php
$query = $db->query("SELECT description, price FROM vehicle_service_items WHERE service_id = {$service_id} ORDER BY id DESC");

if ($query->num_rows > 0) {
    echo <<<HTML
    <section>
        <h2>Mão de Obra</h2>
        <table>
            <thead>
                <tr>
                    <th scope="col">Descrição</th>
                    <th scope="col" aria-label="valor">Preço</th>
                </tr>
            </thead>
            <tbody>
    HTML;

    while ($item = $query->fetch_object()) {
        $price = number_format($item->price, 2, ',', '.');
        echo <<<HTML
            <tr>
                <td>{$item->description}</td>
                <td aria-label="valor">{$price}€</td>
            </tr>
        HTML;
    }

    echo <<<HTML
            </tbody>
        </table>
    </section>
    HTML;
}
