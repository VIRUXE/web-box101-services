<?php
echo <<<HTML
<!DOCTYPE html data-theme="dark">
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Serviços BOX101</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.css">
</head>
<body class="has-navbar-fixed-top">
    <header>
    <nav class="navbar is-fixed-top" role="navigation" aria-label="main navigation">
    <div class="navbar-brand">
      <a class="navbar-item" href="index.php">BOX101</a>
HTML;

if (isset($logged_user)) {
  echo <<<HTML
      <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navigation">
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
      </a>
    </div>

    <div id="navigation" class="navbar-menu">
      <div class="navbar-start">
        <a class="navbar-item" href="veiculos.php">Veículos</a>
        <a class="navbar-item" href="clientes.php">Clientes</a>
      </div>
    <div class="navbar-end">
      <div class="navbar-item">
          <strong>$logged_user</strong>
      </div>
      <div class="navbar-item">
          <a class="button" href="logout.php">Terminar Sessão</a>
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
