<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =====================================================
                CONFIGURAÇÃO SUPABASE (PDO)
   ===================================================== */
$host = 'db.cmanjzahwgoehjggvoss.supabase.co';
$dbname = 'postgres';
$user = 'postgres';
$password = 'G@brielli2025!';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname;port=5432", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    exit("Erro na conexão com o Supabase: " . $e->getMessage());
}

/* =====================================================
                FUNÇÃO DE LOGIN
   ===================================================== */
function login($pdo)
{
    if (!empty($_POST["email"]) && !empty($_POST['senha'])) {
        $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);
        $senha = trim($_POST['senha']);

        $stmt = $pdo->prepare("SELECT * FROM login WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($senha, $user['senha'])) {
            return $user;
        }
    }
    return false;
}

/* =====================================================
            FUNÇÃO DE CADASTRO DE USUÁRIO
   ===================================================== */
function cadastrarUsuario($pdo)
{
    if (isset($_POST['cadastrar'])) {
        $usuario = trim($_POST['usuario']);
        $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);
        $senha = $_POST['senha'];
        $repetir_senha = $_POST['repetir_senha'];
        $palavra_chave = trim($_POST['palavra_chave']);

        if (!$email) {
            $_SESSION['erro'] = "E-mail inválido!";
            return false;
        }
        if ($senha !== $repetir_senha) {
            $_SESSION['erro'] = "As senhas não coincidem!";
            return false;
        }

        $stmt = $pdo->prepare("SELECT 1 FROM login WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $_SESSION['erro'] = "E-mail já cadastrado!";
            return false;
        }

        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt_insert = $pdo->prepare("
            INSERT INTO login (usuario, email, senha, palavra_chave)
            VALUES (:usuario, :email, :senha, :palavra_chave)
        ");
        $sucesso = $stmt_insert->execute([
            ':usuario' => $usuario,
            ':email' => $email,
            ':senha' => $senha_hash,
            ':palavra_chave' => $palavra_chave
        ]);

        if ($sucesso) {
            header("Location: login.php?cadastro=sucesso");
            exit();
        } else {
            $_SESSION['erro'] = "Erro ao criar conta. Tente novamente.";
            return false;
        }
    }
}

/* =====================================================
                FUNÇÕES DE EVENTOS
   ===================================================== */
function listarEventos($futuros = true) {
    global $pdo;
    if ($futuros) {
        $stmt = $pdo->prepare("SELECT * FROM formulario WHERE data_evento >= CURRENT_DATE ORDER BY data_evento ASC, hora_evento ASC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM formulario ORDER BY data_evento ASC, hora_evento ASC");
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function curtirEvento($id_evento, $usuario) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM curtidas WHERE id_evento = :id_evento AND usuario = :usuario");
    $stmt->execute([':id_evento'=>$id_evento, ':usuario'=>$usuario]);
    if(!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt = $pdo->prepare("INSERT INTO curtidas (id_evento, usuario) VALUES (:id_evento, :usuario)");
        $stmt->execute([':id_evento'=>$id_evento, ':usuario'=>$usuario]);
    }
}

function descurtirEvento($id_evento, $usuario) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM curtidas WHERE id_evento = :id_evento AND usuario = :usuario");
    $stmt->execute([':id_evento'=>$id_evento, ':usuario'=>$usuario]);
}

function contarCurtidas($id_evento) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM curtidas WHERE id_evento = :id_evento");
    $stmt->execute([':id_evento'=>$id_evento]);
    return (int)$stmt->fetchColumn();
}

function usuarioCurtiu($id_evento, $usuario) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM curtidas WHERE id_evento = :id_evento AND usuario = :usuario");
    $stmt->execute([':id_evento'=>$id_evento, ':usuario'=>$usuario]);
    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}
?>
