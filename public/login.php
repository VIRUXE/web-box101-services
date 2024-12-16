<?php
include '../database.php';

include '../User.class.php';

session_start();

if (User::isLogged()) header('Location: index.php');

include 'header.php';

echo <<<HTML
<section class="section">
    <div class="container">
HTML;

$email = '';

if ($_POST) {
    $email = $db->real_escape_string($_POST['email']);
    $pin   = $db->real_escape_string($_POST['pin']);

    $query = $db->query("SELECT * FROM users WHERE email = '$email' AND pin = '$pin';");

    if ($query->num_rows) {
        $_SESSION['user'] = $query->fetch_assoc();

        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
        header('Location: ' . $redirect);
    } else
        echo '<div class="notification is-danger">E-mail ou PIN inválidos.</div>';
}

echo <<<HTML
        <div class="columns is-centered">
            <div class="column is-3">
                <h1 class="title">Login</h1>
                <form method="post">
                    <div class="field">
                        <div class="control">
                            <input class="input" type="email" name="email" maxlength="50" placeholder="Email" value="$email" required>
                        </div>
                    </div>
                    <div class="field">
                        <div class="control">
                            <input class="input" type="password" name="pin" minlength="4" maxlength="4" placeholder="PIN de 4 Dígitos" autocomplete="off" pattern="\d*" inputmode="numeric" required>
                        </div>
                    </div>
                    <div class="field">
                        <div class="control">
                            <button class="button" name="submit">Entrar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
const email = document.querySelector('input[name="email"]');
const pin   = document.querySelector('input[name="pin"]');

const isValidEmail = email => /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email);
const isValidPin   = pin   => /^\d{4}$/.test(pin);

const submitForm = () => isValidEmail(email.value) && isValidPin(pin.value) && document.getElementsByTagName('form')[0].requestSubmit();

email.addEventListener('change', e => isValidEmail(e.target.value) && pin.focus());
email.addEventListener('input', submitForm);
pin.addEventListener('input', submitForm);
</script>
HTML;

include 'footer.php';
?>