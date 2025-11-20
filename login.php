<?php 
session_start();
require_once "funct.php"; 

$erro = ""; 

if (isset($_POST['acessar'])) {
    $email = trim(strtolower($_POST['email'])); // remove espaços e força minúsculas
    $senha = $_POST['senha'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM login WHERE LOWER(email) = :email");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Verifica senha usando password_verify()
            if (password_verify($senha, $row['senha'])) {
                $_SESSION['usuario'] = $row['usuario'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['tipo_usuario'] = $row['tipo_usuario']; // guarda o tipo no SESSION

                // Redireciona conforme tipo_usuario
                if ($row['tipo_usuario'] === 'promotor') {
                    header("Location: eventos.php");
                    exit();
                } elseif ($row['tipo_usuario'] === 'usuario') {
                    header("Location: usuario.php");
                    exit();
                } else {
                    $erro = "Tipo de usuário inválido!";
                }

            } else {
                $erro = "Senha incorreta!";
            }
        } else {
            $erro = "Email não encontrado!";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao acessar: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Eventos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body id="fundo">

<div id="login">
    <form action="" method="post">
        <h3>Login</h3>

        <input type="email" name="email" placeholder="Informe seu email" required>
        <input type="password" name="senha" placeholder="Informe sua senha" required>
        <input type="submit" name="acessar" value="Acessar">
    </form>

    <?php if (!empty($erro)) : ?>
        <p class="msg erro"><?= $erro ?></p>
    <?php endif; ?>

    <a href="./cadastro.php">Criar conta</a><br>
</div>

<div class="logo-footer">
    <img src="logo.png" alt="Logo" />
</div>

</body>
</html>



