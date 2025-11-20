<?php
require_once "funct.php";

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$emailSessao = $_SESSION['email'];
$msg = "";

$stmt = $pdo->prepare("SELECT * FROM login WHERE email = :email");
$stmt->execute(['email' => $emailSessao]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo "<script>alert('Usuário não encontrado!'); window.location.href='login.php';</script>";
    exit();
}

if (isset($_POST['salvar_tudo'])) {
    $novoNome  = trim($_POST['usuario']);
    $novoEmail = trim($_POST['email']);
    $novaSenha = !empty($_POST['senha']) ? password_hash($_POST['senha'], PASSWORD_DEFAULT) : $usuario['senha'];

    $stmt = $pdo->prepare("
        UPDATE login 
        SET usuario = :usuario, email = :email, senha = :senha 
        WHERE id = :id
    ");

    $res = $stmt->execute([
        'usuario' => $novoNome,
        'email' => $novoEmail,
        'senha' => $novaSenha,
        'id'     => $usuario['id']
    ]);

    if ($res) {
        $_SESSION['email'] = $novoEmail;
        $msg = "<p class='sucesso'>Alterações salvas com sucesso!</p>";
        $usuario['usuario'] = $novoNome;
        $usuario['email']   = $novoEmail;
    } else {
        $msg = "<p class='erro'>Erro ao salvar alterações.</p>";
    }
}

if (isset($_POST['excluir'])) {
    $stmt = $pdo->prepare("DELETE FROM login WHERE id = :id");
    if ($stmt->execute(['id' => $usuario['id']])) {
        session_destroy();
        echo "<script>alert('Conta excluída com sucesso!'); window.location.href='login.php';</script>";
        exit();
    } else {
        $msg = "<p class='erro'>Erro ao excluir conta.</p>";
    }
}

$cidades = $pdo->query("SELECT DISTINCT cidade, estado FROM formulario ORDER BY cidade")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT DISTINCT categoria FROM formulario ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
$datas = $pdo->query("SELECT DISTINCT data_evento FROM formulario ORDER BY data_evento")->fetchAll(PDO::FETCH_COLUMN);
$horas = $pdo->query("SELECT DISTINCT hora_inicio FROM formulario ORDER BY hora_inicio")->fetchAll(PDO::FETCH_COLUMN);

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perfil do Usuário - EventUP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="navbar.css">
<style>
.container { max-width: 600px; margin-top: 25px; }
.campo { margin-bottom: 15px; }
.campo label { font-weight: bold; }
.campo input { width: 100%; padding: 8px; }
.botao-acao { margin-top: 20px; }
</style>
</head>
<body>

<div class="container">
<h2>Perfil do Usuário</h2>
<?= $msg ?>

<form method="POST">

<div class="campo">
    <label>Nome:</label>
    <input type="text" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>">
</div>

<div class="campo">
    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>">
</div>

<div class="campo">
    <label>Documento:</label>
    <input type="text" value="<?= htmlspecialchars($usuario['documento']) ?>" disabled>
</div>

<div class="campo">
    <label>Tipo de Usuário:</label>
    <input type="text" value="<?= htmlspecialchars($usuario['tipo_usuario']) ?>" disabled>
</div>

<div class="campo">
    <label>Data de Nascimento:</label>
    <input type="date" value="<?= htmlspecialchars($usuario['data_nascimento']) ?>" disabled>
</div>

<div class="campo">
    <label>Alterar Senha (opcional):</label>
    <input type="password" name="senha" placeholder="Digite a nova senha (ou deixe vazio)">
</div>

<div class="botao-acao">
    <button type="submit" name="salvar_tudo" class="btn btn-success">Salvar alterações</button>
    <button type="button" onclick="window.location.href='eventos.php'" class="btn btn-secondary">Voltar</button>
    <button type="submit" name="excluir" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir sua conta?');">Excluir Conta</button>
</div>

</form>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
