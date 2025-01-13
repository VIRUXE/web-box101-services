<?php
session_start();

include_once '../User.class.php';
if (!User::isLogged()) header('Location: login.php');
$logged_user = User::getLogged();

include '../database.php';
include 'header.php';

// Get upcoming and past due services (approved)
$upcomingQuery = <<<SQL
    SELECT 
        service.*, v.brand, v.model, v.matricula,
        CONCAT(u.first_name, ' ', u.last_name) as client_name,
        COUNT(item.id) as total_items,
        COALESCE(SUM(item.price), 0) as total_price,
        TIMESTAMPDIFF(DAY, NOW(), service.starting_date) as days_until,
        TIMESTAMPDIFF(HOUR, NOW(), service.starting_date) % 24 as hours_until,
        TIMESTAMPDIFF(MINUTE, NOW(), service.starting_date) % 60 as minutes_until
    FROM vehicle_services service
    JOIN vehicles v ON service.matricula = v.matricula
    LEFT JOIN users u ON service.client_id = u.id
    LEFT JOIN vehicle_service_items item ON service.id = item.service_id
    WHERE service.state = 'APPROVED'
    AND service.starting_date <= NOW()
    GROUP BY service.id
    ORDER BY service.starting_date ASC
    LIMIT 10
SQL;

// Get active services (in progress)
$activeQuery = <<<SQL
    SELECT 
        service.*, v.brand, v.model, v.matricula,
        u.first_name, u.last_name,
        COUNT(CASE WHEN item.status IN ('STARTED', 'PAUSED') THEN 1 END) as active_items,
        COUNT(item.id) as total_items,
        SUM(CASE WHEN item.status = 'SUCCESS' THEN 1 ELSE 0 END) as completed_items,
        SUM(item.price) as total_price,
        GROUP_CONCAT(item.description SEPARATOR '|') as item_descriptions,
        GROUP_CONCAT(item.status SEPARATOR '|') as item_statuses,
        TIMESTAMPDIFF(DAY, service.starting_date, NOW()) as days_duration,
        TIMESTAMPDIFF(HOUR, service.starting_date, NOW()) % 24 as hours_duration,
        TIMESTAMPDIFF(MINUTE, service.starting_date, NOW()) % 60 as minutes_duration
    FROM vehicle_services service
    JOIN vehicles v ON service.matricula = v.matricula
    LEFT JOIN users u ON service.client_id = u.id
    LEFT JOIN vehicle_service_items item ON service.id = item.service_id
    WHERE service.state = 'IN_PROGRESS'
    GROUP BY service.id
    ORDER BY service.starting_date ASC
SQL;

$upcoming = $db->query($upcomingQuery)->fetch_all(MYSQLI_ASSOC) ?: [];
$active = $db->query($activeQuery)->fetch_all(MYSQLI_ASSOC) ?: [];

echo <<<HTML
    <section class="section">
        <div class="container">
            <div class="columns is-vcentered">
                <div class="column">
                    <h1 class="title">Olá $logged_user!</h1>
                    <div class="subtitle">{$logged_user->getLevelTitle()}</div>
                </div>
            </div>
            <hr>

            <h1 class="title">Serviços Agendados</h1>
            <div class="table-container">
                <table class="table is-striped is-fullwidth is-hoverable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Veículo</th>
                            <th>Cliente</th>
                            <th>Datas</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
HTML;

if (empty($upcoming)) {
    echo <<<HTML
                        <tr>
                            <td colspan="7" class="has-text-centered">Sem serviços agendados no momento.</td>
                        </tr>
HTML;
} else {
    foreach ($upcoming as $appointment) {
        $timeUntilStr = "";
        if ($appointment['days_until'] > 0) $timeUntilStr .= "{$appointment['days_until']}d ";
        $timeUntilStr .= "{$appointment['hours_until']}h {$appointment['minutes_until']}m";
        
        $currentDateTime = new DateTime('now');
        $targetDateTime = new DateTime($appointment['starting_date']);
        $interval = $currentDateTime->diff($targetDateTime);
        $daysLeft = $interval->days;
        $class = 'is-dark';
        
        if ($daysLeft > 0) {
            $timeMessage = sprintf(
                '%d dia%s e %d hora%s %s',
                $daysLeft,
                $daysLeft !== 1 ? 's' : '',
                $interval->h,
                $interval->h !== 1 ? 's' : '',
                $interval->invert ? 'em atraso' : 'restantes'
            );
        } else {
            $timeMessage = sprintf(
                '%d hora%s %s',
                $interval->h,
                $interval->h !== 1 ? 's' : '',
                $interval->invert ? 'em atraso' : 'restantes'
            );
        }

        if ($interval->invert === 1 || $daysLeft <= 1) {
            $class = 'is-danger';
        } elseif ($daysLeft <= 3) {
            $class = 'is-warning';
        }

        $starting_date = $appointment['starting_date'] ?? 'N/D';
        $ending_date = $appointment['ending_date'] ?? 'N/D';
        
        echo <<<HTML
                        <tr>
                            <td><a href="servico.php?id={$appointment['id']}" class="has-text-weight-bold">{$appointment['id']}</a></td>
                            <td>
                                <strong>{$appointment['brand']} {$appointment['model']}</strong><br>
                                <small><a href="veiculo.php?matricula={$appointment['matricula']}">{$appointment['matricula']}</a></small>
                            </td>
                            <td>
                                <strong><a href="cliente.php?id={$appointment['client_id']}">{$appointment['client_name']}</a></strong><br>
                            </td>
                            <td>
                                <strong>Início:</strong> {$starting_date}<br>
                                <strong>Fim:</strong> {$ending_date}<br>
                                <small class="tag {$class}">{$timeMessage}</small>
                            </td>
                            <td>
                                <strong>€{$appointment['total_price']}</strong>
                            </td>
                        </tr>
HTML;
    }
}

echo <<<HTML
                    </tbody>
                </table>
            </div>

            <h1 class="title mt-6">Serviços em Curso</h1>
            <div class="table-container">
                <table class="table is-striped is-fullwidth is-hoverable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Veículo</th>
                            <th>Cliente</th>
                            <th>Duração</th>
                            <th>Progresso</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
HTML;

if (empty($active)) {
    echo <<<HTML
                        <tr>
                            <td colspan="7" class="has-text-centered">Sem serviços em curso no momento.</td>
                        </tr>
HTML;
} else {
    foreach ($active as $service) {
        $durationStr = "";
        if ($service['days_duration'] > 0) $durationStr .= "{$service['days_duration']}d ";
        $durationStr .= "{$service['hours_duration']}h {$service['minutes_duration']}m";

        $currentDateTime = new DateTime('now');
        $targetDateTime = new DateTime($service['starting_date']);
        $interval = $currentDateTime->diff($targetDateTime);
        $daysLeft = $interval->days;
        $class = 'is-dark';
        
        if ($daysLeft > 0) {
            $timeMessage = sprintf(
                '%d dia%s e %d hora%s %s',
                $daysLeft,
                $daysLeft !== 1 ? 's' : '',
                $interval->h,
                $interval->h !== 1 ? 's' : '',
                $interval->invert ? 'em atraso' : 'restantes'
            );
        } else {
            $timeMessage = sprintf(
                '%d hora%s %s',
                $interval->h,
                $interval->h !== 1 ? 's' : '',
                $interval->invert ? 'em atraso' : 'restantes'
            );
        }

        if ($interval->invert === 1 || $daysLeft <= 1) {
            $class = 'is-danger';
        } elseif ($daysLeft <= 3) {
            $class = 'is-warning';
        }

        $starting_date = $service['starting_date'] ?? 'N/D';
        $ending_date   = $service['ending_date'] ?? 'N/D';
        
        $progress = ($service['completed_items'] / $service['total_items']) * 100;
        echo <<<HTML
                        <tr>
                            <td><a href="servico.php?id={$service['id']}" class="has-text-weight-bold">{$service['id']}</a></td>
                            <td>
                                <strong>{$service['brand']} {$service['model']}</strong><br>
                                <small><a href="veiculo.php?matricula={$service['matricula']}">{$service['matricula']}</a></small>
                            </td>
                            <td>
                                <strong><a href="cliente.php?id={$service['client_id']}">{$service['first_name']} {$service['last_name']}</a></strong><br>
                            </td>
                            <td>
                                <strong>Início:</strong> {$starting_date}<br>
                                <strong>Fim:</strong> {$ending_date}<br>
                                <small class="tag {$class}">{$timeMessage}</small>
                            </td>
                            <td>
                                <progress class="progress is-small" value="{$progress}" max="100">{$progress}%</progress>
                                <small>{$service['completed_items']}/{$service['total_items']} items</small>
                            </td>
                            <td>
                                <strong>€{$service['total_price']}</strong>
                            </td>
                        </tr>
HTML;
    }
}

echo <<<HTML
                    </tbody>
                </table>
            </div>
        </div>
    </section>
HTML;

include 'footer.php';