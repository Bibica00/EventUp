<?php
require_once "funct.php"; 
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$emailLogado = $_SESSION['email'];
$mensagem = "";

if (isset($_POST['excluir'])) {
    $id_evento = $_POST['id_evento'];
    $stmt_check = $pdo->prepare("SELECT imagem FROM formulario WHERE id = :id AND usuario = :usuario");
    $stmt_check->execute([
        ':id' => $id_evento,
        ':usuario' => $emailLogado
    ]);
    $evento_img = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$evento_img) {
        $mensagem = "<p class='erro'>Você não tem permissão para excluir este evento.</p>";
    } else {
        if (!empty($evento_img['imagem']) && file_exists($evento_img['imagem'])) {
            unlink($evento_img['imagem']);
        }
        $stmt = $pdo->prepare("DELETE FROM formulario WHERE id = :id AND usuario = :usuario");
        $stmt->execute([
            ':id' => $id_evento,
            ':usuario' => $emailLogado
        ]);

        $mensagem = "<p class='sucesso'>Evento excluído com sucesso!</p>";
    }
}

if (isset($_POST['editar'])) {
    $id_evento = $_POST['id_evento'];
    $stmt_check = $pdo->prepare("SELECT imagem FROM formulario WHERE id = :id AND usuario = :usuario");
    $stmt_check->execute([
        ':id' => $id_evento,
        ':usuario' => $emailLogado
    ]);
    $evento_old = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$evento_old) {
        $mensagem = "<p class='erro'>Você não tem permissão para atualizar este evento.</p>";
    } else {
        $nome = trim($_POST['nome']);
        $categoria = trim($_POST['categoria']);
        $descricao = trim($_POST['descricao']);
        $faixa_etaria = trim($_POST['faixa_etaria']);
        $data_evento = $_POST['data_evento'];
        $hora_inicio = $_POST['hora_inicio'];
        $hora_fim = $_POST['hora_fim'];
        $pago = $_POST['pago'];
        $link_ingresso = ($pago === "Sim") ? trim($_POST['link_ingresso']) : null;
        $valor_ingresso = ($pago === "Sim") ? str_replace(',', '.', trim($_POST['valor_ingresso'])) : null;
        $localizacao = trim($_POST['localizacao']);
        $cidade = trim($_POST['cidade']);
        $estado = trim($_POST['estado']);
        $telefone = !empty($_POST['telefone']) ? trim($_POST['telefone']) : null;

        $imagem = null;
        if (!empty($_FILES['imagem']['name'])) {
            $pasta = "uploads/";
            if (!is_dir($pasta)) mkdir($pasta, 0777, true);

            $nomeImagem = time() . "_" . basename($_FILES["imagem"]["name"]);
            $caminhoCompleto = $pasta . $nomeImagem;
            $tipoArquivo = strtolower(pathinfo($caminhoCompleto, PATHINFO_EXTENSION));
            $tiposPermitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($tipoArquivo, $tiposPermitidos)) {
                move_uploaded_file($_FILES["imagem"]["tmp_name"], $caminhoCompleto);
                $imagem = $caminhoCompleto;

                if (!empty($evento_old['imagem']) && file_exists($evento_old['imagem'])) {
                    unlink($evento_old['imagem']);
                }
            }
        }

        $sql = "UPDATE formulario SET 
            nome = :nome,
            categoria = :categoria,
            descricao = :descricao,
            faixa_etaria = :faixa_etaria,
            data_evento = :data_evento,
            hora_inicio = :hora_inicio,
            hora_fim = :hora_fim,
            pago = :pago,
            link_ingresso = :link_ingresso,
            valor_ingresso = :valor_ingresso,
            localizacao = :localizacao,
            cidade = :cidade,
            estado = :estado,
            telefone = :telefone";

        if ($imagem) $sql .= ", imagem = :imagem";

        $sql .= " WHERE id = :id AND usuario = :usuario";

        $stmt = $pdo->prepare($sql);
        $params = [
            ':nome' => $nome,
            ':categoria' => $categoria,
            ':descricao' => $descricao,
            ':faixa_etaria' => $faixa_etaria,
            ':data_evento' => $data_evento,
            ':hora_inicio' => $hora_inicio,
            ':hora_fim' => $hora_fim,
            ':pago' => $pago,
            ':link_ingresso' => $link_ingresso,
            ':valor_ingresso' => $valor_ingresso,
            ':localizacao' => $localizacao,
            ':cidade' => $cidade,
            ':estado' => $estado,
            ':telefone' => $telefone,
            ':id' => $id_evento,
            ':usuario' => $emailLogado
        ];

        if ($imagem) $params[':imagem'] = $imagem;

        $stmt->execute($params);
        $mensagem = "<p class='sucesso'>Evento atualizado com sucesso!</p>";
    }
}

$stmt = $pdo->prepare("SELECT * FROM formulario WHERE usuario = :usuario ORDER BY data_evento DESC");
$stmt->execute([':usuario' => $emailLogado]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerenciar Eventos</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="navbar.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <h2>Meus Eventos</h2>
    <?= $mensagem ?>

<?php if($eventos): ?>
    <div class="row">
        <?php foreach($eventos as $evento): ?>
            <div class="col-md-6 mb-4">
                <div class="card evento-item" style="cursor:pointer;">

                    <?php if($evento['imagem'] && file_exists($evento['imagem'])): ?>
                        <img src="<?= $evento['imagem'] ?>" class="card-img-top" alt="Imagem do evento">
                    <?php endif; ?>

                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($evento['nome']) ?></h5>
                    </div>

                </div>
                <div class="evento-edicao" style="display:none;">

                    <div class="card card-body mt-2">
                        <form method="POST" enctype="multipart/form-data">

                            <input type="hidden" name="id_evento" value="<?= $evento['id'] ?>">

                            <div class="mb-2">
                                <input type="text" name="nome" value="<?= htmlspecialchars($evento['nome']) ?>" required class="form-control">
                            </div>

                            <div class="mb-2">
                                <input type="text" name="categoria" value="<?= htmlspecialchars($evento['categoria']) ?>" required class="form-control">
                            </div>

                            <div class="mb-2">
                                <textarea name="descricao" rows="3" class="form-control"><?= htmlspecialchars($evento['descricao']) ?></textarea>
                            </div>

                            <div class="mb-2">
                                <input type="text" name="faixa_etaria" value="<?= htmlspecialchars($evento['faixa_etaria']) ?>" class="form-control">
                            </div>

                            <div class="mb-2">
                                <input type="date" name="data_evento" value="<?= $evento['data_evento'] ?>" class="form-control">
                            </div>

                            <div class="mb-2">
                                <input type="time" name="hora_inicio" value="<?= $evento['hora_inicio'] ?>" class="form-control">
                                <input type="time" name="hora_fim" value="<?= $evento['hora_fim'] ?>" class="form-control mt-1">
                            </div>

                            <div class="mb-2">
                                <select name="pago" class="form-control">
                                    <option value="Não" <?= ($evento['pago']=="Não"?"selected":"") ?>>Não</option>
                                    <option value="Sim" <?= ($evento['pago']=="Sim"?"selected":"") ?>>Sim</option>
                                </select>
                            </div>

                            <div class="mb-2">
                                <input type="url" name="link_ingresso" value="<?= htmlspecialchars($evento['link_ingresso']) ?>" placeholder="Link do ingresso" class="form-control">
                            </div>

                            <div class="mb-2">
                                <input type="text" name="valor_ingresso" value="<?= htmlspecialchars($evento['valor_ingresso']) ?>" placeholder="Valor do ingresso" class="form-control">
                            </div>

                            <div class="mb-2">
                                <input type="text" name="localizacao" value="<?= htmlspecialchars($evento['localizacao']) ?>" class="form-control">
                            </div>

                            <div class="mb-2">
                                <input type="text" name="cidade" value="<?= htmlspecialchars($evento['cidade']) ?>" class="form-control">
                                <input type="text" name="estado" value="<?= htmlspecialchars($evento['estado']) ?>" class="form-control mt-1">
                            </div>

                            <div class="mb-2">
                                <input type="tel" name="telefone" value="<?= htmlspecialchars($evento['telefone']) ?>" class="form-control">
                            </div>

                            <div class="mb-2">
                                <input type="file" name="imagem" class="form-control">
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" name="editar" class="btn btn-primary btn-sm">Salvar</button>
                                <button type="submit" name="excluir" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este evento?');">Excluir</button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>Você ainda não cadastrou nenhum evento.</p>
<?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const eventoItems = document.querySelectorAll('.evento-item');
    
    eventoItems.forEach(function(item) {
        item.addEventListener('click', function() {
            const edicao = this.nextElementSibling;
            const todosEdicoes = document.querySelectorAll('.evento-edicao');
            
            todosEdicoes.forEach(function(ed) {
                if (ed !== edicao) {
                    ed.style.display = 'none';
                }
            });
            
            if (edicao.style.display === 'none' || edicao.style.display === '') {
                edicao.style.display = 'block';
            } else {
                edicao.style.display = 'none';
            }
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>


