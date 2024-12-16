<?php
// Format the time taken to create the page in miliseconds - 0.000ms
$total_time = round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) * 1000;

echo <<<HTML
    <footer class="footer">
        <div class="content has-text-centered">
            <p><strong>Painel de Serviço BOX101</strong></p>
            <p class="has-text-grey-darker">
                Tempo de execução: {$total_time} ms
            </p>
        </div>
    </footer>

    <script>
        document.querySelectorAll('.navbar-burger').forEach(el => el.onclick = () => {
            el.classList.toggle('is-active');
            document.getElementById(el.dataset.target).classList.toggle('is-active');
        });
    </script>
</body>
</html>
HTML;

if (isset($db)) $db->close();
?>