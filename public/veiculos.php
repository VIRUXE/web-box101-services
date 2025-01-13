<?php
include '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

include 'header.php';
include '../database.php';

const BASE_QUERY = "SELECT * FROM vehicles";

$search = isset($_GET['pesquisa']) ? $db->real_escape_string($_GET['pesquisa']) : NULL;

$query = $db->query(BASE_QUERY . ($search ? " WHERE matricula LIKE '%$search%' OR brand LIKE '%$search%' OR model LIKE '%$search%' OR colour LIKE '%$search%' OR trim LIKE '%$search%' OR notes LIKE '%$search%'" : "") . ' ORDER BY registration_date DESC;');

echo <<<HTML
    <section class="section">
        <div class="container">
            <h1 class="title">Veículos</h1>
            <form id="searchForm" method="get" onsubmit="return false">
                <div class="field">
                    <div class="control has-icons-left">
                        <span class="icon is-left">
                            <i class="fas fa-search"></i>
                        </span>
                        <input class="input" type="text" name="pesquisa" id="searchInput" placeholder="Matrícula, Marca, Modelo, Cor, Nome do Cliente" value="{$search}" minlength="2">
                    </div>
                </div>
            </form>
            <div id="searchResults">
                <div id="searchStatus" class="notification is-hidden"></div>
                <hr>
                <div class="buttons is-grouped">
                    <a href="criar_veiculo.php" class="button">Criar Veículo</a>
                </div>
                <div class="table-container">
                    <table class="table is-fullwidth is-striped is-hoverable">
                        <thead>
                            <tr>
                                <th>Matrícula</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Cor</th>
                                <th>Versão</th>
                                <th>Notas</th>
                                <th>Data de Registo</th>
                            </tr>
                        </thead>
                        <tbody id="vehiclesList">
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
        searchStatus.textContent = 'Não foram encontrados veículos com o termo de pesquisa.';
    } else {
        searchStatus.classList.add('is-success');
        searchStatus.textContent = count === 1 
            ? 'Foi encontrado 1 veículo.'
            : `Foram encontrados ${count} veículos.`;
    }
}

const formatDate = dateStr => new Date(dateStr).toLocaleDateString('pt-PT');

async function searchVehicles() {
    try {
        const response = await fetch(`api/search_vehicles.php?pesquisa=${encodeURIComponent(searchInput.value.trim())}`);
        const data = await response.json();

        if (data.error) throw new Error(data.error);

        updateSearchStatus(data.vehicles.length);

        document.getElementById('vehiclesList').innerHTML = data.vehicles.map(vehicle => `
            <tr>
                <td><a href="veiculo.php?matricula=${vehicle.matricula}" class="has-text-link">${vehicle.matricula}</a></td>
                <td>${vehicle.brand}</td>
                <td>${vehicle.model}</td>
                <td>${vehicle.colour || ''}</td>
                <td>${vehicle.trim || ''}</td>
                <td>${vehicle.notes || ''}</td>
                <td>${formatDate(vehicle.registration_date)}</td>
            </tr>
        `).join('');

    } catch (error) {
        searchStatus.classList.remove('is-hidden', 'is-success');
        searchStatus.classList.add('is-warning');
        searchStatus.textContent = 'Erro ao pesquisar veículos: ' + error.message;
    }
}

searchInput.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(searchVehicles, 300);
});

// Initial search to load latest records
searchVehicles();
</script>

<?php
include 'footer.php';