<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

include 'header.php';
include '../database.php';

$search = isset($_GET['pesquisa']) ? $db->real_escape_string($_GET['pesquisa']) : NULL;

echo <<<HTML
    <section class="section">
        <div class="container">
            <h1 class="title">Clientes</h1>
            <form id="searchForm" method="get" onsubmit="return false">
                <div class="field">
                    <div class="control has-icons-left">
                        <span class="icon is-left">
                            <i class="fas fa-search"></i>
                        </span>
                        <input class="input" type="text" name="pesquisa" id="searchInput" placeholder="Nome, Email, Telefone, NIF" value="$search" minlength="2">
                    </div>
                </div>
            </form>
            <div id="searchResults">
                <div id="searchStatus" class="notification is-hidden"></div>
                <hr>
                <div class="buttons is-grouped">
                    <a href="criar_cliente.php" class="button">Criar Cliente</a>
                </div>
                <div class="table-container">
                    <table class="table is-fullwidth is-striped is-hoverable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>NIF</th>
                                <th>Morada</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody id="clientsList">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
HTML;
?>

<script>
const searchInput = document.getElementById('searchInput');
const searchStatus = document.getElementById('searchStatus');
let searchTimeout;

function updateSearchStatus(count) {
    searchStatus.classList.remove('is-hidden', 'is-warning', 'is-success');
    
    if (count === 0) {
        searchStatus.classList.add('is-warning');
        searchStatus.textContent = 'Não foram encontrados clientes com o termo de pesquisa.';
    } else {
        searchStatus.classList.add('is-success');
        searchStatus.textContent = count === 1 
            ? 'Foi encontrado 1 cliente.'
            : `Foram encontrados ${count} clientes.`;
    }
}

async function searchClients() {
    try {
        const response = await fetch(`api/search_clients.php?pesquisa=${encodeURIComponent(searchInput.value.trim())}`);
        const data = await response.json();

        if (data.error) throw new Error(data.error);

        updateSearchStatus(data.clients.length);

        document.getElementById('clientsList').innerHTML = data.clients.map(client => `
            <tr>
                <td><a href="cliente.php?id=${client.id}" class="has-text-link">${client.first_name} ${client.last_name || ''}</a></td>
                <td>${client.email || ''}</td>
                <td>${client.phone}</td>
                <td>${client.nif || ''}</td>
                <td>${client.address || ''}</td>
                <td>${client.notes || ''}</td>
            </tr>
        `).join('');

    } catch (error) {
        searchStatus.classList.remove('is-hidden', 'is-success');
        searchStatus.classList.add('is-warning');
        searchStatus.textContent = 'Erro ao pesquisar clientes: ' + error.message;
    }
}

searchInput.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(searchClients, 300);
});

// Initial search to load latest records
searchClients();
</script>

<?php
include 'footer.php';