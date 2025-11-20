<?php
require_once "funct.php"; 
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$emailLogado = $_SESSION['email'];
$mensagem = "";

try {
    $stmt_usuario = $pdo->prepare("SELECT data_nascimento FROM login WHERE email = :email");
    $stmt_usuario->execute([':email' => $emailLogado]);
    $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

    $data_nascimento = $usuario['data_nascimento'] ?? null;
    $idade = !empty($data_nascimento) ? (int)date_diff(date_create($data_nascimento), date_create('now'))->y : 0;
} catch (PDOException $e) {
    $mensagem = "<p class='erro'>Erro ao verificar usuário: " . htmlspecialchars($e->getMessage()) . "</p>";
}

if (isset($_POST['cadastrar'])) {
    if ($idade < 18) {
        $mensagem = "<p class='erro'>Promotor menor de 18 anos não pode cadastrar evento.</p>";
    } else {
        try {
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
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO formulario 
                (nome, categoria, descricao, faixa_etaria, data_evento, hora_inicio, hora_fim, pago, link_ingresso, valor_ingresso, localizacao, cidade, estado, telefone, imagem, usuario)
                VALUES 
                (:nome, :categoria, :descricao, :faixa_etaria, :data_evento, :hora_inicio, :hora_fim, :pago, :link_ingresso, :valor_ingresso, :localizacao, :cidade, :estado, :telefone, :imagem, :usuario)
            ");

            $sucesso = $stmt->execute([
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
                ':imagem' => $imagem,
                ':usuario' => $emailLogado
            ]);

            if ($sucesso) {
                echo "<script>
                    alert('Evento cadastrado com sucesso!');
                    window.location.href='eventos.php';
                </script>";
                exit();
            } else {
                $mensagem = "<p class='erro'>Erro ao cadastrar evento.</p>";
            }
        } catch (PDOException $e) {
            $mensagem = "<p class='erro'>Erro inesperado: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Evento</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container-cadastro">
    <h2>Cadastro de Evento</h2>
    <?= $mensagem ?>

    <form method="POST" enctype="multipart/form-data" class="form-cadastro">
        <fieldset>
            <div class="div-campo">
                <input type="text" name="nome" placeholder="Nome do evento" required>
            </div>

            <div class="div-campo">
                <input type="text" name="categoria" placeholder="Categoria" required>
            </div>

            <div class="div-campo">
                <textarea name="descricao" rows="4" placeholder="Descrição do evento" required></textarea>
            </div>

            <div class="div-campo">
                <input type="text" name="faixa_etaria" placeholder="Faixa etária permitida" required>
            </div>

            <div class="div-campo">
                <label>Data do evento:</label>
                <input type="date" name="data_evento" required>
            </div>

            <div class="div-campo">
                <label>Hora de início:</label>
                <input type="time" name="hora_inicio" required>
            </div>

            <div class="div-campo">
                <label>Hora de término:</label>
                <input type="time" name="hora_fim" required>
            </div>

            <div class="div-campo">
                <label>Evento pago?</label>
                <div class="radio-group">
                    <label class="radio-container">Não
                        <input type="radio" name="pago" value="Não" checked onclick="toggleLink()">
                        <span class="checkmark"></span>
                    </label>
                    <label class="radio-container">Sim
                        <input type="radio" name="pago" value="Sim" onclick="toggleLink()">
                        <span class="checkmark"></span>
                    </label>
                </div>
            </div>

            <div id="linkDiv" style="display:none;">
                <div class="div-campo">
                    <input type="url" name="link_ingresso" placeholder="Link do ingresso">
                </div>
                <div class="div-campo">
                    <input type="text" name="valor_ingresso" placeholder="Valor do ingresso (R$)">
                </div>
            </div>

            <div class="div-campo">
                <input type="text" name="localizacao" placeholder="Localização" required>
            </div>

            <div class="div-campo">
                <input type="text" name="cidade" placeholder="Cidade" required>
            </div>

            <div class="div-campo">
                <input type="text" name="estado" maxlength="2" placeholder="Estado (UF)" required>
            </div>

            <div class="div-campo">
                <input type="tel" name="telefone" placeholder="Telefone (opcional)">
            </div>

            <div class="div-campo">
                <label>Imagem do evento (opcional):</label>
                <input type="file" name="imagem" accept="image/*">
            </div>

            <input type="submit" name="cadastrar" value="Cadastrar Evento">
        </fieldset>
    </form>
</div>

<script>
function toggleLink() {
    const sim = document.querySelector('input[name="pago"][value="Sim"]');
    document.getElementById("linkDiv").style.display = sim.checked ? "block" : "none";
}
window.onload = toggleLink;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

