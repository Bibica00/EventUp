<?php
require_once "funct.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$emailUsuario = $_SESSION['email'] ?? '';

$cidades = $pdo->query("SELECT DISTINCT cidade, estado FROM formulario ORDER BY cidade")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT DISTINCT categoria FROM formulario ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
$datas = $pdo->query("SELECT DISTINCT data_evento FROM formulario ORDER BY data_evento")->fetchAll(PDO::FETCH_COLUMN);
$horas = $pdo->query("SELECT DISTINCT hora_inicio FROM formulario ORDER BY hora_inicio")->fetchAll(PDO::FETCH_COLUMN);
?>

<link rel="stylesheet" href="navbar.css">

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="eventos.php">Event<span>UP</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto align-items-center">

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Cidade</a>
          <ul class="dropdown-menu">
            <?php foreach($cidades as $c): ?>
              <li><a class="dropdown-item" href="eventos.php?cidade=<?= urlencode($c['cidade']) ?>&estado=<?= urlencode($c['estado']) ?>"><?= htmlspecialchars($c['cidade'].'-'.$c['estado']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Categoria</a>
          <ul class="dropdown-menu">
            <?php foreach($categorias as $cat): ?>
              <li><a class="dropdown-item" href="eventos.php?categoria=<?= urlencode($cat) ?>"><?= htmlspecialchars($cat) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Data</a>
          <ul class="dropdown-menu">
            <?php foreach($datas as $d): ?>
              <li><a class="dropdown-item" href="eventos.php?data=<?= urlencode($d) ?>"><?= htmlspecialchars(date('d/m/Y', strtotime($d))) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Hora</a>
          <ul class="dropdown-menu">
            <?php foreach($horas as $h): ?>
              <li><a class="dropdown-item" href="eventos.php?hora=<?= urlencode($h) ?>"><?= htmlspecialchars(date('H:i', strtotime($h))) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>

        <li class="nav-item busca-evento">
          <form method="GET" action="eventos.php" class="form-busca">
            <input type="search" name="pesquisa" placeholder="Buscar eventos..." value="<?= $_GET['pesquisa'] ?? '' ?>">
            <button type="button" class="limpar-pesquisa">✖</button>
          </form>
        </li>

        <li class="nav-item"><a class="nav-link" href="eventos.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="curtidas.php">Curtidas</a></li>
        <li class="nav-item"><a class="nav-link" href="cadastroEvento.php">Cadastrar</a></li>
        <li class="nav-item"><a class="nav-link" href="editarEventos.php">Gerenciar</a></li>
        <li class="nav-item"><a class="nav-link" href="editarPerfil.php">Perfil</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Sair</a></li>

      </ul>
    </div>
  </div>
</nav>

<style>
.navbar { background-color: #e0e0e0; padding: 10px 20px; }
.navbar-brand { font-size: 1.6rem; font-weight: 900; color: #000; }
.navbar-brand span { color: #ff4a67; }
.navbar-nav .nav-link { color: #000; font-weight: 500; padding: 8px 10px; transition: 0.3s; }
.navbar-nav .nav-link:hover { color: #ff4a67; }
.busca-evento input[type="search"] {
  border-radius: 25px;
  padding: 8px 40px 8px 18px;
  border: 1px solid #ccc;
  width: 100%;
  max-width: 400px;
  background: #fff url('data:image/svg+xml;utf8,<svg fill="%23ff4a67" height="18" viewBox="0 0 512 512" width="18" xmlns="http://www.w3.org/2000/svg"><path d="M505 442.7L405.3 343c28.4-34.9 45.7-79.3 45.7-127C451 96.5 354.5 0 229.5 0S8 96.5 8 216.5 104.5 433 229.5 433c47.8 0 92.2-17.3 127-45.7l99.7 99.7c4.7 4.7 12.3 4.7 17 0l32.6-32.6c4.7-4.7 4.7-12.3 0-17zM229.5 393c-97.2 0-176-78.8-176-176S132.3 41 229.5 41s176 78.8 176 176-78.8 176-176 176z"/></svg>') no-repeat right 15px center;
  background-size: 18px;
}
.form-busca { display: flex; align-items: center; gap: 6px; }
.form-busca .limpar-pesquisa { background: none; border: none; font-size: 16px; cursor: pointer; color: #ff4a67; font-weight: bold; margin-left: 5px; }
.dropdown-menu { border-radius: 8px; border: 1px solid #ccc; }
@media (max-width: 991px) {
  .navbar-nav { text-align: center; gap: 10px; }
  .busca-evento input[type="search"] { max-width: 100%; margin: 10px auto; }
  .navbar-collapse { background-color: #f8f8f8; border-radius: 8px; padding: 15px 10px; }
}
.navbar-toggler { border: none; }
.navbar-toggler-icon {
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 100 80' width='25' height='25' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='100' height='15' fill='%23ff4a67'/%3E%3Crect y='30' width='100' height='15' fill='%23ff4a67'/%3E%3Crect y='60' width='100' height='15' fill='%23ff4a67'/%3E%3C/svg%3E");
}
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function() {
  $(".limpar-pesquisa").on("click", function(){
    window.location.href = "eventos.php";
  });

  $(".dropdown-menu a").on("click", function(e){
    e.preventDefault();
    const href = $(this).attr("href");
    window.location.href = href;
  });

  $(".form-busca").on("submit", function(e){
    e.preventDefault();
    const query = $(this).find('input[name="pesquisa"]').val().trim();
    window.location.href = query !== "" ? "eventos.php?pesquisa=" + encodeURIComponent(query) : "eventos.php";
  });
});
</script>

