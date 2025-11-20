<?php
require_once "funct.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$emailUsuario = $_SESSION['email'];

$sql = "SELECT f.* 
        FROM formulario f 
        JOIN curtidas c ON f.id = c.id_evento 
        WHERE c.usuario = :usuario 
        AND f.data_evento >= CURRENT_DATE 
        ORDER BY f.data_evento ASC, f.hora_inicio ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':usuario' => $emailUsuario]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cidades = $pdo->query("SELECT DISTINCT cidade, estado FROM formulario ORDER BY cidade")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT DISTINCT categoria FROM formulario ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
$datas = $pdo->query("SELECT DISTINCT data_evento FROM formulario ORDER BY data_evento")->fetchAll(PDO::FETCH_COLUMN);
$horas = $pdo->query("SELECT DISTINCT hora_inicio FROM formulario ORDER BY hora_inicio")->fetchAll(PDO::FETCH_COLUMN);

function usuarioCurtiuPDO($pdo, $id_evento, $usuario) {
    $stmt = $pdo->prepare("SELECT * FROM curtidas WHERE id_evento = :id_evento AND usuario = :usuario");
    $stmt->execute([':id_evento' => $id_evento, ':usuario' => $usuario]);
    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Eventos Curtidos - EventUP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="navbar.css">
<style>
@import url('https://fonts.googleapis.com/css?family=Montserrat:300,400,500');
body { margin: 0; font-family: "Montserrat", sans-serif; background-color: #f4f8fa; color: #3e4555; }
a { text-decoration: none; transition: 0.3s; }
a:hover { color: #ff4a67; }

.card-container img { width: 100%; height: 180px; object-fit: cover; object-position: center; display: block; }
.grid-eventos { display:flex; flex-wrap:wrap; justify-content:center; gap:25px; padding:30px 10px; }
.card-container { background:#fff; border-radius:12px; width:100%; max-width:340px; box-shadow:0 5px 15px rgba(0,0,0,0.1); overflow:hidden; display:flex; flex-direction:column; position:relative; cursor:pointer; }
.card-body { padding:15px; text-align:center; }
.botoes-acoes { display:flex; justify-content:center; gap:15px; margin-top:10px; flex-wrap:wrap; }
.icon-btn { background:none; border:none; cursor:pointer; font-size:22px; transition:0.2s; }
.icon-btn:hover { transform:scale(1.2); }
.data-quadro { position:absolute; top:10px; right:10px; background-color:#ff4a67; color:#fff; padding:5px 10px; border-radius:8px; font-weight:bold; font-size:0.9rem; z-index:10; }
.btn-ingresso { display:inline-block; padding:6px 12px; background-color:#ff4a67; color:#fff; border-radius:6px; text-decoration:none; margin-top:5px; }
.btn-ingresso:hover { background-color:#e03b58; }
@media (max-width:768px) { .card-container { max-width:100%; } }
.detalhes-evento { display: none; }
.intro-text { text-align: center; padding: 20px; font-size: 1.5rem; font-weight: bold; }
</style>
</head>
<body>

<?php include "navbar1.php"; ?>

<div class="intro-text">
  <p>Eventos Curtidos</p>
</div>

<div class="grid-eventos" id="eventos">
<?php if(!empty($eventos)): ?>
  <?php foreach($eventos as $evento):
    $idEvento = $evento['id'];
    $imagemEvento = !empty($evento['imagem']) ? $evento['imagem'] : 'assets/img/speaker/default.jpg';
    $curtiu = usuarioCurtiuPDO($pdo, $idEvento, $emailUsuario);
  ?>
  <div class="card-container" id="evento<?= $idEvento ?>">
    <div class="data-quadro"><?= date('d/m/Y', strtotime($evento['data_evento'])) ?></div>
    <img src="<?= htmlspecialchars($imagemEvento) ?>" alt="Evento">
    <div class="card-body">
      <h5><?= htmlspecialchars($evento['nome']) ?></h5>
      <p><?= htmlspecialchars($evento['descricao']) ?></p>
      <div class="detalhes-evento">
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
            Comprar Ingresso <?php if(!empty($evento['valor_ingresso'])) echo 'R$ '.htmlspecialchars($evento['valor_ingresso']); ?>
          </a>
        <?php endif; ?>
      </div>
      <div class="botoes-acoes">
        <button class="icon-btn curtir-btn" id="btn-curtir-<?= $idEvento ?>" data-id="<?= $idEvento ?>"><?= $curtiu ? '❤️' : '🤍' ?></button>
        <button class="icon-btn compartilhar-btn" data-link="<?= 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '#evento' . $idEvento ?>">↗️</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
<?php else: ?>
  <p class="text-center">Nenhum evento curtido.</p>
<?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(".curtir-btn").on("click", function(e) {
  e.stopPropagation();
  let id_evento = $(this).data("id");
  let botao = $(this);
  $.ajax({
    url: "curtir_ajax.php",
    type: "POST",
    data: { id_evento: id_evento },
    dataType: "json",
    success: function(res) {
      if (res.status === "curtido") botao.text("❤️");
      else if (res.status === "descurtido") {
        botao.text("🤍");
        botao.closest('.card-container').fadeOut(300, function() {
          $(this).remove();
          if($('.card-container').length === 0) {
            $('.grid-eventos').html('<p class="text-center">Nenhum evento curtido.</p>');
          }
        });
      }
      else alert(res.mensagem || "Erro ao curtir.");
    },
    error: function(xhr) {
      console.error(xhr.responseText);
      alert("Erro ao processar curtida.");
    }
  });
});

$(".card-container").on("click", function(e) {
  if ($(e.target).closest(".botoes-acoes").length) return;
  $(".detalhes-evento").not($(this).find(".detalhes-evento")).slideUp();
  $(this).find(".detalhes-evento").slideToggle();
});

$(".compartilhar-btn").on("click", function(e) {
  e.stopPropagation();
  const link = $(this).data('link');
  navigator.clipboard.writeText(link).then(function() {
    alert('Link copiado!');
  }).catch(function(err) {
    const tempInput = document.createElement('input');
    tempInput.value = link;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    alert('Link copiado!');
  });
});
</script>

</body>
</html>
