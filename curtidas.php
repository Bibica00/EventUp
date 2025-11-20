<?php
require_once "funct.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
$emailUsuario = $_SESSION['email'];

$stmt = $pdo->prepare("
    SELECT f.*
    FROM formulario f
    JOIN curtidas c ON f.id = c.id_evento
    WHERE c.usuario = :usuario AND c.tipo_usuario = 'promotor'
    ORDER BY f.data_evento ASC, f.hora_inicio ASC
");
$stmt->execute([':usuario' => $emailUsuario]);
$eventosCurtidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cidades = $pdo->query("SELECT DISTINCT cidade, estado FROM formulario ORDER BY cidade")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT DISTINCT categoria FROM formulario ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
$datas = $pdo->query("SELECT DISTINCT data_evento FROM formulario ORDER BY data_evento")->fetchAll(PDO::FETCH_COLUMN);
$horas = $pdo->query("SELECT DISTINCT hora_inicio FROM formulario ORDER BY hora_inicio")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Curtidas - Promotor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="navbar.css">
<style>
body { margin:0; font-family:"Montserrat",sans-serif; background:#f4f8fa; color:#3e4555; }
.grid-eventos { display:flex; flex-wrap:wrap; justify-content:center; gap:25px; padding:30px 10px; }
.card-container { background:#fff; border-radius:12px; width:100%; max-width:340px; box-shadow:0 5px 15px rgba(0,0,0,0.1); overflow:hidden; display:flex; flex-direction:column; position:relative; cursor:pointer; }
.card-container img { width:100%; height:180px; object-fit:cover; object-position:center; display:block; }
.card-body { padding:15px; text-align:center; }
.icon-btn { background:none; border:none; cursor:pointer; font-size:20px; }
.icon-btn:hover { transform:scale(1.2); }
.intro-text { text-align:center; font-weight:700; color:#000; margin:2rem 10px; font-size:1.2rem; }
.data-quadro { position:absolute; top:10px; right:10px; background-color:#ff4a67; color:#fff; padding:5px 10px; border-radius:8px; font-weight:bold; }
.btn-ingresso { display:inline-block; padding:6px 12px; background:#ff4a67; color:#fff; border-radius:6px; text-decoration:none; margin-top:5px; }
.btn-ingresso:hover { background-color:#e03b58; }
.detalhes-evento { display:none; }
.botoes-acoes { display:flex; justify-content:center; gap:10px; margin-top:10px; }
</style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="intro-text">
  <p>Eventos que você curtiu:</p>
</div>

<div class="grid-eventos">
<?php if(!empty($eventosCurtidos)): ?>
  <?php foreach($eventosCurtidos as $evento): ?>
    <?php $imagemEvento = !empty($evento['imagem']) ? $evento['imagem'] : 'assets/img/speaker/default.jpg'; ?>
    <div class="card-container">
      <div class="data-quadro"><?= date('d/m/Y', strtotime($evento['data_evento'])) ?></div>
      <img src="<?= htmlspecialchars($imagemEvento) ?>" alt="Evento">
      <div class="card-body">
        <h5><?= htmlspecialchars($evento['nome']) ?></h5>
        <p><?= htmlspecialchars($evento['descricao']) ?></p>

        <div class="detalhes-evento">
          <p><strong>Categoria:</strong> <?= htmlspecialchars($evento['categoria']) ?></p>
          <p><strong>Horário:</strong> <?= date('H:i', strtotime($evento['hora_inicio'])) ?> - <?= date('H:i', strtotime($evento['hora_fim'])) ?></p>
          <p><strong>Localização:</strong> <?= htmlspecialchars($evento['localizacao']) ?></p>
          <p><?= htmlspecialchars($evento['cidade'].'-'.$evento['estado']) ?></p>
        </div>

        <form method="post" action="eventos.php" class="botoes-acoes">
          <input type="hidden" name="id_evento" value="<?= $evento['id'] ?>">
          <button type="submit" name="descurtir" class="icon-btn">❤️</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p class="text-center">Nenhum evento curtido.</p>
<?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(".card-container").on("click", function(e) {
    if ($(e.target).closest("form").length) return;
    $(".detalhes-evento").not($(this).find(".detalhes-evento")).slideUp();
    $(this).find(".detalhes-evento").slideToggle();
});
</script>

</body>
</html>
