<?php
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
    'Sunday' => 'domingo',
    'Monday' => 'segunda-feira',
    'Tuesday' => 'terça-feira',
    'Wednesday' => 'quarta-feira',
    'Thursday' => 'quinta-feira',
    'Friday' => 'sexta-feira',
    'Saturday' => 'sábado'
];

$weekday = $weekdays[$currentTime->format('l')];
$day = $currentTime->format('j');
$month = $months[(int)$currentTime->format('n')];
$shortMonth = $shortMonths[(int)$currentTime->format('n')];
$year = $currentTime->format('Y');
$timeStr = $currentTime->format('H:i:s');

$dateInPortuguese = "{$weekday}, {$day} de {$month} de {$year}";
$shortDateInPortuguese = "{$day} {$shortMonth}";
$timestamp = $currentTime->getTimestamp();

echo <<<HTML
<!DOCTYPE html data-theme="dark">
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Serviços BOX101</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .datetime-display {
            color: #7a7a7a;
            letter-spacing: 0.5px;
        }
        .time-display {
            font-family: monospace;
            font-size: 1.1em;
        }
        .datetime-mobile {
            display: none;
        }
        @media screen and (max-width: 768px) {
            .datetime-desktop {
                display: none;
            }
            .datetime-mobile {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .datetime-display {
                font-size: 0.9em;
            }
            .navbar-brand {
                flex: 1;
                justify-content: space-between;
            }
        }
    </style>
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
        });
    </script>
</head>
<body class="has-navbar-fixed-top">
    <header>
    <nav class="navbar is-fixed-top" role="navigation" aria-label="main navigation">
    <div class="navbar-brand">
      <a class="navbar-item" href="index.php">BOX101</a>
HTML;

if (isset($logged_user)) {
  echo <<<HTML
      <span class="navbar-item datetime-display datetime-mobile">
        <span>{$shortDateInPortuguese}</span>
        <span class="time-display" id="time-display-mobile">{$timeStr}</span>
      </span>
      <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navigation">
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
      </a>
    </div>

    <div id="navigation" class="navbar-menu">
      <div class="navbar-start">
        <a class="navbar-item" href="veiculos.php">Veículos</a>
        <a class="navbar-item" href="clientes.php">Clientes</a>
        <a class="navbar-item" href="servicos.php">Serviços</a>
      </div>

      <div class="navbar-end">
        <span class="navbar-item datetime-display datetime-desktop">
          <span>{$dateInPortuguese} • </span>
          <span class="time-display" id="time-display-desktop">{$timeStr}</span>
        </span>
        <div class="navbar-item">
          <strong>$logged_user</strong>
        </div>
        <div class="navbar-item">
          <a class="button" href="logout.php">Terminar Sessão</a>
        </div>
      </div>
    </div>
HTML;
} else {
  if (basename($_SERVER['PHP_SELF']) == 'login.php') {
    /* echo <<<HTML
            <div class="navbar-item">
                <a class="button disabled" href="register.php">Registar</a>
            </div>
            HTML; */
  }
}

echo <<<HTML
      </div>
    </div>
  </nav>
</header>
HTML;
