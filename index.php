<?php
require_once "funct.php"; 
if (session_status() === PHP_SESSION_NONE) session_start();

$emailUsuario = $_SESSION['email'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM formulario WHERE data_evento >= CURRENT_DATE ORDER BY data_evento ASC, hora_inicio ASC");
$stmt->execute();
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$eventosFiltrados = $eventos ?? [];

if (!empty($_GET['pesquisa'])) {
    $busca = strtolower($_GET['pesquisa']);
    $eventosFiltrados = array_filter($eventosFiltrados, fn($e) => str_contains(strtolower($e['nome']), $busca));
}
if (!empty($_GET['cidade'])) {
    $eventosFiltrados = array_filter($eventosFiltrados, fn($e) => $e['cidade'] === $_GET['cidade'] && $e['estado'] === $_GET['estado']);
}
if (!empty($_GET['categoria'])) {
    $eventosFiltrados = array_filter($eventosFiltrados, fn($e) => $e['categoria'] === $_GET['categoria']);
}
if (!empty($_GET['data'])) {
    $eventosFiltrados = array_filter($eventosFiltrados, fn($e) => $e['data_evento'] === $_GET['data']);
}
if (!empty($_GET['hora'])) {
    $eventosFiltrados = array_filter($eventosFiltrados, fn($e) => $e['hora_inicio'] === $_GET['hora']);
}

$cidades = $pdo->query("SELECT DISTINCT cidade, estado FROM formulario")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT DISTINCT categoria FROM formulario")->fetchAll(PDO::FETCH_COLUMN);
$datas = $pdo->query("SELECT DISTINCT data_evento FROM formulario")->fetchAll(PDO::FETCH_COLUMN);
$horas = $pdo->query("SELECT DISTINCT hora_inicio FROM formulario")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Eventos - EventUP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

@import url('https://fonts.googleapis.com/css?family=Montserrat:300,400,500');

body {
  margin: 0;
  font-family: "Montserrat", sans-serif;
  background-color: #f4f8fa;
  color: #3e4555;
}
a { text-decoration: none; transition: 0.3s; }
a:hover { color: #ff4a67; }
img { max-width: 100%; height: auto; }

.navbar {
  background-color: #e0e0e0;
  padding: 10px 20px;
}
.navbar-brand { font-size: 1.6rem; font-weight: 900; color: #000; }
.navbar-brand span { color: #ff4a67; }
.navbar-nav .nav-link { color: #000; font-weight: 500; padding: 8px 10px; transition: color 0.3s; }
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

.navbar-toggler { border: none; }
.navbar-toggler-icon {
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 100 80' width='25' height='25' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='100' height='15' fill='%23ff4a67'/%3E%3Crect y='30' width='100' height='15' fill='%23ff4a67'/%3E%3Crect y='60' width='100' height='15' fill='%23ff4a67'/%3E%3C/svg%3E");
}

.evento img {
  width: 100%;
  height: auto;
  object-fit: cover;
  max-height: 450px;
}

.intro-text {
  text-align: center;
  font-weight: 700;
  color: #000;
  margin: 4rem 10px;
  font-size: 1.2rem;
  line-height: 1.6;
}

.grid-eventos {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 25px;
  padding: 30px 10px;
}

.card-container {
  background-color: #fff;
  border-radius: 12px;
  width: 100%;
  max-width: 340px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  position: relative;
  cursor: pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card-container:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-container.expanded {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.card-container img {
  width: 100%;
  height: 220px;
  object-fit: cover;
}

.card-body {
  padding: 15px;
  text-align: center;
}

.info-basica {
  display: block;
}

.info-completa {
  display: none;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid #eee;
  text-align: left;
}

.card-container.expanded .info-basica {
  display: block;
}

.card-container.expanded .info-completa {
  display: block;
}

.botoes-acoes {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 10px;
  flex-wrap: wrap;
}

.icon-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 20px;
  transition: transform 0.2s ease;
}
.icon-btn:hover { transform: scale(1.2); }

.data-quadro {
  position: absolute;
  top: 10px;
  right: 10px;
  background-color: #ff4a67;
  color: #fff;
  padding: 5px 10px;
  border-radius: 8px;
  font-weight: bold;
  font-size: 0.9rem;
  z-index: 10;
}

.btn-ingresso {
  display: inline-block;
  padding: 6px 12px;
  background-color: #ff4a67;
  color: #fff;
  border-radius: 6px;
  text-decoration: none;
  margin-top: 5px;
}
.btn-ingresso:hover { background-color: #e03b58; }

.form-busca {
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-busca .limpar-pesquisa {
  background: none;
  border: none;
  font-size: 16px;
  cursor: pointer;
  color: #ff4a67;
  font-weight: bold;
  margin-left: 5px;
}

@media (max-width: 768px) {
  .intro-text { font-size: 1rem; margin: 3rem 10px; }
  .card-container { max-width: 100%; }
}

</style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Event<span>UP</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto align-items-center">
        
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Cidade</a>
          <ul class="dropdown-menu">
            <?php foreach($cidades as $c): ?>
              <li><a class="dropdown-item" href="?cidade=<?= urlencode($c['cidade']) ?>&estado=<?= urlencode($c['estado']) ?>"><?= htmlspecialchars($c['cidade'].'-'.$c['estado']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Categoria</a>
          <ul class="dropdown-menu">
            <?php foreach($categorias as $cat): ?>
              <li><a class="dropdown-item" href="?categoria=<?= urlencode($cat) ?>"><?= htmlspecialchars($cat) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Data</a>
          <ul class="dropdown-menu">
            <?php foreach($datas as $d): ?>
              <li><a class="dropdown-item" href="?data=<?= urlencode($d) ?>"><?= htmlspecialchars(date('d/m/Y', strtotime($d))) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Hora</a>
          <ul class="dropdown-menu">
            <?php foreach($horas as $h): ?>
              <li><a class="dropdown-item" href="?hora=<?= urlencode($h) ?>"><?= htmlspecialchars(date('H:i', strtotime($h))) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>

        <li class="nav-item busca-evento">
          <form method="GET" class="form-busca">
            <input type="search" name="pesquisa" placeholder="Buscar eventos...">
            <button type="button" class="limpar-pesquisa">✖</button>
          </form>
        </li>

        <li class="nav-item">
          <a class="nav-link text-danger limpar-filtros" href="#"></a>
        </li>

        <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#eventos">Eventos</a></li>
        <li class="nav-item"><a class="nav-link text-success" href="login.php">Login</a></li>

      </ul>
    </div>
  </div>
</nav>

<div class="evento">
  <img src="evento.jpg" alt="Evento">
</div>

<div class="intro-text">
  <p>Bem-vindo a EventUP</p>
  <p>O portal de eventos criado para conectar você às melhores experiências da cidade.</p>
</div>

<div class="grid-eventos" id="eventos">
<?php if(!empty($eventosFiltrados)): ?>
<?php foreach($eventosFiltrados as $evento):
$idEvento = $evento['id'];
$imagemEvento = !empty($evento['imagem']) ? $evento['imagem'] : 'assets/img/speaker/default.jpg';
?>
<div class="card-container" data-event-id="<?= $idEvento ?>">
  <div class="data-quadro"><?= date('d/m/Y', strtotime($evento['data_evento'])) ?></div>
  <img src="<?= htmlspecialchars($imagemEvento) ?>" alt="Evento">
  <div class="card-body">
    <div class="info-basica">
      <h5><?= htmlspecialchars($evento['nome']) ?></h5>
      <p><?= htmlspecialchars($evento['descricao']) ?></p>
      
      <div class="botoes-acoes">
        <a href="login.php" class="icon-btn">🤍</a>
        <button class="icon-btn compartilhar-btn" data-link="<?= 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '#evento' . $idEvento ?>">↗️</button>
      </div>
    </div>
    <div class="info-completa">
      <p><strong>Categoria:</strong> <?= htmlspecialchars($evento['categoria']) ?></p>
      <p><strong>Faixa etária:</strong> <?= htmlspecialchars($evento['faixa_etaria']) ?></p>
      <p><strong>Horário:</strong> <?= date('H:i', strtotime($evento['hora_inicio'])) ?> - <?= date('H:i', strtotime($evento['hora_fim'])) ?></p>
      <p><strong>Localização:</strong> <?= htmlspecialchars($evento['localizacao']) ?></p>
      <p><?= htmlspecialchars($evento['cidade'].'-'.$evento['estado']) ?></p>

      <?php if(!empty($evento['telefone'])): ?>
        <p><strong>Telefone:</strong> <?= htmlspecialchars($evento['telefone']) ?></p>
      <?php endif; ?>

      <?php if(!empty($evento['link_ingresso'])): ?>
        <a href="<?= htmlspecialchars($evento['link_ingresso']) ?>" target="_blank" class="btn-ingresso">
          Comprar Ingresso <?php if(!empty($evento['valor_ingresso'])) echo 'R$ '.$evento['valor_ingresso']; ?>
        </a>
      <?php endif; ?>
    </div>
    
  </div>
</div>
<?php endforeach; else: ?>
<p class="text-center">Nenhum evento encontrado.</p>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$(function(){
  function fecharTodosCards() {
    $('.card-container.expanded').removeClass('expanded');
  }
  $('.card-container').on('click', function(e) {
    if ($(e.target).hasClass('icon-btn') || $(e.target).closest('.icon-btn').length) {
      return;
    }

    const $card = $(this);
    if ($card.hasClass('expanded')) {
      $card.removeClass('expanded');
    } else {
      fecharTodosCards();
      $card.addClass('expanded');
    }
  });

  $('.compartilhar-btn').on('click', function(e) {
    e.stopPropagation(); 
    const link = $(this).data('link');
    navigator.clipboard.writeText(link).then(function() {
      alert('Link copiado!');
    }).catch(function(err) {
      console.error('Erro ao copiar link: ', err);
      const tempInput = document.createElement('input');
      tempInput.value = link;
      document.body.appendChild(tempInput);
      tempInput.select();
      document.execCommand('copy');
      document.body.removeChild(tempInput);
      alert('Link copiado!');
    });
  });
  $(".limpar-filtros").on("click", function(e){
    e.preventDefault();
    window.location.href = window.location.pathname;
  });
  $(".limpar-pesquisa").on("click", function(){
    $("input[name='pesquisa']").val("");
    $(".form-busca").submit();
  });

});
</script>

</body>
</html>
