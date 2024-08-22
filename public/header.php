<?php
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Serviços BOX101</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
</head>
<body>
    <header>
    <nav class="navbar" role="navigation" aria-label="main navigation">
    <div class="navbar-brand">
      <a class="navbar-item" href="index.php">BOX101</a>
HTML;

if (isset($logged_user)) {
  echo <<<HTML
      <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbarBasicExample">
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
      </a>
    </div>

    <div id="navbarBasicExample" class="navbar-menu">
    <div class="navbar-start">
      <a class="navbar-item" href="carros.php">Carros</a>

      <div class="navbar-item has-dropdown is-hoverable">
        <a class="navbar-link" href="clientes.php">Clientes</a>

        <!-- <div class="navbar-dropdown">
          <a class="navbar-item">About</a>
          <a class="navbar-item is-selected">Jobs</a>
          <a class="navbar-item">Contact</a>
          <hr class="navbar-divider">
          <a class="navbar-item">Report an issue</a>
        </div> -->
      </div>
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
