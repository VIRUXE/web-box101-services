<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../User.class.php';

$currentTime = new DateTime('2025-01-10T16:13:37Z');

// Portuguese month names (short)
$shortMonths = [
    1 => 'jan', 'fev', 'mar', 'abr',
    'mai', 'jun', 'jul', 'ago',
    'set', 'out', 'nov', 'dez'
];

// Portuguese month names
$months = [
    1 => 'janeiro', 'fevereiro', 'março', 'abril',
    'maio', 'junho', 'julho', 'agosto',
    'setembro', 'outubro', 'novembro', 'dezembro'
];

// Portuguese weekday names
$weekdays = [
    'Sunday'    => 'domingo',
    'Monday'    => 'segunda-feira',
    'Tuesday'   => 'terça-feira',
    'Wednesday' => 'quarta-feira',
    'Thursday'  => 'quinta-feira',
    'Friday'    => 'sexta-feira',
    'Saturday'  => 'sábado'
];

$weekday    = $weekdays[$currentTime->format('l')];
$day        = $currentTime->format('j');
$month      = $months[(int)$currentTime->format('n')];
$shortMonth = $shortMonths[(int)$currentTime->format('n')];
$year       = $currentTime->format('Y');
$timeStr    = $currentTime->format('H:i:s');

$dateInPortuguese      = "{$weekday}, {$day} de {$month} de {$year}";
$shortDateInPortuguese = "{$day} {$shortMonth}";
$timestamp             = $currentTime->getTimestamp();

$currentPage    = basename($_SERVER['PHP_SELF']);
$activeIndex    = $currentPage === 'index.php' ? ' is-active' : '';
$activeVeiculos = $currentPage === 'veiculos.php' ? ' is-active' : '';
$activeClientes = $currentPage === 'clientes.php' ? ' is-active' : '';

echo <<<HTML
<!DOCTYPE html data-theme="dark">
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Serviços BOX101</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
HTML;

if (User::isLogged()) {
    echo <<<HTML
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const timeDisplays = document.querySelectorAll('.time-display');
            const startTime = {$timestamp};
            const startDate = new Date(startTime * 1000);
            
            function updateTime() {
                const now = new Date();
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const seconds = now.getSeconds().toString().padStart(2, '0');
                timeDisplays.forEach(display => {
                    display.textContent = `\${hours}:\${minutes}:\${seconds}`;
                });
            }
            
            // Update immediately and then every second
            updateTime();
            setInterval(updateTime, 1000);

            // Burger menu functionality
            const burger = document.querySelector('.navbar-burger');
            const menu = document.querySelector('.navbar-menu');
            burger.addEventListener('click', () => {
                burger.classList.toggle('is-active');
                menu.classList.toggle('is-active');
            });
        });
    </script>
HTML;
}

echo <<<HTML
</head>
<body class="has-navbar-fixed-top">
HTML;

if (User::isLogged()) {
    echo <<<HTML
    <nav class="navbar is-fixed-top" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
            <a class="navbar-item" href="index.php">
                <strong>BOX101</strong>
            </a>

            <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="mainNavbar">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="mainNavbar" class="navbar-menu">
            <div class="navbar-start">
                <a href="index.php" class="navbar-item{$activeIndex}">
                    <span class="icon"><i class="fas fa-home"></i></span>
                    <span>Início</span>
                </a>
                <a href="veiculos.php" class="navbar-item{$activeVeiculos}">
                    <span class="icon"><i class="fas fa-car"></i></span>
                    <span>Veículos</span>
                </a>
                <a href="clientes.php" class="navbar-item{$activeClientes}">
                    <span class="icon"><i class="fas fa-users"></i></span>
                    <span>Clientes</span>
                </a>
                
                <div class="navbar-item has-dropdown is-hoverable">
                    <a class="navbar-link">
                        <span class="icon"><i class="fas fa-plus"></i></span>
                        <span>Criar</span>
                    </a>
                    <div class="navbar-dropdown">
                        <a href="criar_veiculo.php" class="navbar-item">
                            <span class="icon"><i class="fas fa-car"></i></span>
                            <span>Novo Veículo</span>
                        </a>
                        <a href="criar_cliente.php" class="navbar-item">
                            <span class="icon"><i class="fas fa-user-plus"></i></span>
                            <span>Novo Cliente</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="navbar-end">
                <div class="navbar-item datetime-desktop">
                    <div class="datetime-display">
                        <span>{$dateInPortuguese}</span>
                        <span class="time-display"></span>
                    </div>
                </div>
                <div class="navbar-item">
                    <div class="buttons">
                        <a href="logout.php" class="button is-light">
                            <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                            <span>Sair</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
HTML;
}
echo <<<HTML
</body>
</html>
HTML;
