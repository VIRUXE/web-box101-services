<?php
declare(strict_types=1);

include '../User.class.php';
include '../Service.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

include '../database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : NULL;
if (!$id) {
    echo '<p role="alert">ID não especificado</p>';
    exit;
}

$service = Service::getById($id);
if (!$service) {
    echo '<p role="alert">Serviço não encontrado</p>';
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

$state = $service->getState();
$total_cost = $parts->customer_total + $labor->total;
$starting_date = $service->getStartingDate() ?? 'N/D';
$ending_date = $service->getEndingDate() ?? 'N/D';
$starting_odometer = $service->getStartingOdometer() ?: 'N/D';
$finished_odometer = $service->getFinishedOdometer() ?: 'N/D';

echo <<<HTML
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordem de Serviço #{$service->getId()}</title>
    <style>
        @media not print {
            button[type="button"] {
                position: fixed;
                top: 1rem;
                right: 1rem;
                padding: 0.5rem 1rem;
                cursor: pointer;
            }
        }
        @media print {
            @page {
                margin: 0.5cm;
                size: A4;
            }
            button[type="button"] {
                display: none;
            }
            article {
                margin: 0;
                padding: 0;
            }
            section {
                page-break-inside: avoid;
            }
            section + section {
                page-break-before: auto;
                margin-block-start: 2rem;
            }
            thead {
                display: table-header-group;
            }
            img[role="presentation"] {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                opacity: 0.1;
                pointer-events: none;
                z-index: -1;
            }
        }
        :root {
            --border-color: #dbdbdb;
            font-family: system-ui, -apple-system, sans-serif;
            line-height: 1.4;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            margin: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        article {
            padding: 2rem;
            max-width: 21cm;
            margin: 0 auto;
            min-height: 29.7cm;
            background: white;
        }
        header[role="banner"] {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-block-end: 2rem;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            break-inside: avoid;
        }
        header[role="banner"] h1 {
            font-size: 1.5rem;
            margin-block-end: 0.5rem;
        }
        header[role="banner"] time {
            color: #666;
            font-size: 0.9rem;
        }
        header[role="banner"] aside {
            text-align: end;
        }
        header[role="banner"] aside p:first-child {
            font-size: 1.2rem;
            font-weight: bold;
            margin-block-end: 0.5rem;
        }
        .details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-block-end: 2rem;
        }
        section {
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            margin-block-start: 2rem;
        }
        .details section {
            margin-block-start: 0;
        }
        h2 {
            font-size: 1.1rem;
            margin-block-end: 1rem;
            padding-block-end: 0.5rem;
            border-block-end: 1px solid var(--border-color);
        }
        dl {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.5rem 1rem;
        }
        dt {
            font-weight: bold;
        }
        @media print {
            dl {
                display: block;
            }
            dt {
                margin-block-start: 0.5rem;
            }
            dt:first-child {
                margin-block-start: 0;
            }
            dd {
                margin-inline-start: 0;
            }
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-block: 1rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: start;
            border-block-end: 1px solid var(--border-color);
        }
        th {
            font-weight: bold;
            background: #f8f8f8;
        }
        [aria-label="valor"] {
            text-align: end;
        }
        thead {
            display: table-header-group;
        }
        img[role="presentation"] {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.1;
            pointer-events: none;
            z-index: -1;
        }
    </style>
</head>
<body>
    <button type="button" onclick="window.print()">Imprimir</button>
    <article>
        <!-- <img src="assets/logo-watermark.png" role="presentation" alt=""> -->
        <header role="banner">
            <div>
                <h1>Ordem de Serviço #{$service->getId()}</h1>
                <time datetime="{$service->getCreatedAt()}">Emitido em {$service->getCreatedAt()}</time>
            </div>
            <aside>
                <p>{$vehicle->plate}</p>
                <p>{$vehicle->brand} {$vehicle->model}</p>
                <p>{$vehicle->colour}</p>
            </aside>
        </header>
        
        <div class="details">
            <section>
                <h2>Dados do Cliente</h2>
                <dl>
                    <dt>Nome</dt>
                    <dd>{$client->first_name} {$client->last_name}</dd>
                    <dt>Telefone</dt>
                    <dd>{$client->phone}</dd>
                    <dt>Email</dt>
                    <dd>{$client->email}</dd>
                </dl>
            </section>

            <section>
                <h2>Detalhes do Serviço</h2>
                <dl>
                    <dt>Data de Início</dt>
                    <dd>{$starting_date}</dd>
                    <dt>Data de Conclusão</dt>
                    <dd>{$ending_date}</dd>
                    <dt>Quilómetros Iniciais</dt>
                    <dd>{$starting_odometer}</dd>
                    <dt>Quilómetros Finais</dt>
                    <dd>{$finished_odometer}</dd>
                </dl>
            </section>
        </div>
        <section>
            <h2>Resumo de Custos</h2>
            <table>
                <tbody>
                    <tr>
                        <td>Total em Peças</td>
                        <td aria-label="valor">{$parts->customer_total}€</td>
                    </tr>
                    <tr>
                        <td>Total em Mão de Obra</td>
                        <td aria-label="valor">{$labor->total}€</td>
                    </tr>
                    <tr>
                        <th scope="row">Total Final</th>
                        <td aria-label="valor"><strong>{$total_cost}€</strong></td>
                    </tr>
                </tbody>
            </table>
        </section>
HTML;

$service_id = $service->getId();
require 'print_items_view.php';
require 'print_parts_view.php';

echo <<<HTML
    </article>
</body>
</html>
HTML;
