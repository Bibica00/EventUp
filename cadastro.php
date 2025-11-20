<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "funct.php"; 

function validarDocumento($documento, $tipo_usuario) {
    $documento = preg_replace('/[^0-9]/', '', $documento);
    if ($tipo_usuario === 'usuario') {
        if (strlen($documento) === 11) {
            return ['valido' => true, 'tipo' => 'CPF', 'documento' => $documento];
        } elseif (strlen($documento) >= 7 && strlen($documento) <= 12) {
            return ['valido' => true, 'tipo' => 'RG', 'documento' => $documento];
        } else {
            return ['valido' => false, 'erro'];
        }
    } elseif ($tipo_usuario === 'promotor') {
        if (strlen($documento) === 14) {
            return ['valido' => true, 'tipo' => 'CNPJ', 'documento' => $documento];
        } else {
            return ['valido' => false, 'erro'];
        }
    } 
    
    return ['valido' => false, 'erro' => 'Tipo de usuário inválido'];
}

function formatarDocumento($documento, $tipo) {
    $documento = preg_replace('/[^0-9]/', '', $documento);
    
    switch ($tipo) {
        case 'CPF':
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $documento);
        case 'CNPJ':
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $documento);
        case 'RG':
            return $documento;
        default:
            return $documento;
    }
}

if (isset($_POST['cadastrar'])) {
    $usuario = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $repetir_senha = $_POST['repetir_senha'];
    $documento = trim($_POST['documento']);
    $tipo_usuario = $_POST['tipo_usuario']; 
    $data_nascimento = $_POST['data_nascimento']; 

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Email inválido!');</script>";
    } elseif ($senha !== $repetir_senha) {
        echo "<script>alert('As senhas não coincidem!');</script>";
    } else {
        $validacaoDocumento = validarDocumento($documento, $tipo_usuario);
        
        if (!$validacaoDocumento['valido']) {
            echo "<script>alert('" . $validacaoDocumento['erro'] . "');</script>";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM login WHERE email = :email");
                $stmt->execute([':email' => $email]);
                if ($stmt->fetch()) {
                    echo "<script>alert('Este e-mail já está cadastrado!');</script>";
                } else {
                    $documento_formatado = formatarDocumento($documento, $validacaoDocumento['tipo']);
                    
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                    $stmt_insert = $pdo->prepare("
                        INSERT INTO login (usuario, email, senha, documento, tipo_usuario, data_nascimento)
                        VALUES (:usuario, :email, :senha, :documento, :tipo_usuario, :data_nascimento)
                    ");
                    $stmt_insert->execute([
                        ':usuario' => $usuario,
                        ':email' => $email,
                        ':senha' => $senha_hash,
                        ':documento' => $documento_formatado,
                        ':tipo_usuario' => $tipo_usuario,
                        ':data_nascimento' => $data_nascimento
                    ]);

                    echo "
                    <div id='chave'>
                        <p class='msg sucesso'>Cadastro efetuado com sucesso! Redirecionando para login...</p>
                    </div>
                    <script>
                        setTimeout(() => { window.location.href = 'login.php'; }, 2000);
                    </script>";
                    exit;
                }
            } catch (PDOException $e) {
                echo "<script>alert('Erro ao criar conta: " . htmlspecialchars($e->getMessage()) . "');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro de Usuário</title>
<link rel="stylesheet" href="style.css">
<style>
.documento-info {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
    display: none;
}

.documento-info.usuario {
    color: #2196F3;
}

.documento-info.promotor {
    color: #4CAF50;
}

.documento-info.erro {
    color: #f44336;
}

.div-campo input:focus + .documento-info {
    display: block;
}
</style>
</head>

<body id="fundo">
    <div class="container-cadastro">
        <form action="" method="post" class="form-cadastro" id="formCadastro">
            <fieldset>
                <legend>Cadastro de Usuário</legend>

                <div class="div-campo">
                    <input type="text" name="usuario" placeholder="Nome completo" required>
                </div>

                <div class="div-campo">
                    <input type="email" name="email" placeholder="Email" required>
                </div>

                <div class="div-campo">
                    <input type="password" name="senha" placeholder="Senha" required>
                </div>

                <div class="div-campo">
                    <input type="password" name="repetir_senha" placeholder="Repita a senha" required>
                </div>

                <div class="div-campo">
                    <input type="text" name="documento" id="documento" placeholder="RG/CPF/CNPJ" required 
                           oninput="formatarDocumentoInput(this)" 
                           onblur="validarDocumentoInput(this)">
                    <div id="documentoInfo" class="documento-info"></div>
                </div>

                <div class="div-campo div-campo-date">
                    <label class="data-label">Data de nascimento:</label>
                    <input type="date" name="data_nascimento" placeholder="dd/mm/aaaa" required>
                </div>

                <div class="div-campo">
                    <label class="radio-label">Fazer login como:</label>
                    <div class="radio-group">
                        <label class="radio-container">Usuário
                            <input type="radio" name="tipo_usuario" value="usuario" required onchange="atualizarInfoDocumento()">
                            <span class="checkmark"></span>
                        </label>
                        <label class="radio-container">Promotor
                            <input type="radio" name="tipo_usuario" value="promotor" required onchange="atualizarInfoDocumento()">
                            <span class="checkmark"></span>
                        </label>
                    </div>
                </div>

                <div class="div-campo">
                    <input type="submit" name="cadastrar" value="Criar Conta">
                </div>
            </fieldset>
        </form>

        <h2><a href="login.php">Já tem uma conta? Faça login</a></h2>
    </div>
    
    <div class="logo-footer">
        <img src="logo.png" alt="Logo" />
    </div>

    <script>
    function atualizarInfoDocumento() {
        const tipoUsuario = document.querySelector('input[name="tipo_usuario"]:checked');
        const infoDiv = document.getElementById('documentoInfo');
        
        if (tipoUsuario) {
            if (tipoUsuario.value === 'usuario') {
                infoDiv.innerHTML = 'Para usuário: CPF ou RG ';
                infoDiv.className = 'documento-info usuario';
            } else if (tipoUsuario.value === 'promotor') {
                infoDiv.innerHTML = 'Para promotor: CNPJ';
                infoDiv.className = 'documento-info promotor';
            }
            infoDiv.style.display = 'block';
        }
    }

    function formatarDocumentoInput(input) {
        let valor = input.value.replace(/\D/g, '');
        const tipoUsuario = document.querySelector('input[name="tipo_usuario"]:checked');
        
        if (!tipoUsuario) {
            document.getElementById('documentoInfo').innerHTML = 'Selecione o tipo de usuário primeiro';
            document.getElementById('documentoInfo').className = 'documento-info erro';
            document.getElementById('documentoInfo').style.display = 'block';
            return;
        }

        if (tipoUsuario.value === 'usuario') {
            if (valor.length <= 11) {
                if (valor.length > 9) {
                    valor = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                } else if (valor.length > 6) {
                    valor = valor.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
                } else if (valor.length > 3) {
                    valor = valor.replace(/(\d{3})(\d{1,3})/, '$1.$2');
                }
            }
        } else if (tipoUsuario.value === 'promotor') {

            if (valor.length <= 14) {
                if (valor.length > 12) {
                    valor = valor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
                } else if (valor.length > 8) {
                    valor = valor.replace(/(\d{2})(\d{3})(\d{3})(\d{1,4})/, '$1.$2.$3/$4');
                } else if (valor.length > 5) {
                    valor = valor.replace(/(\d{2})(\d{3})(\d{1,3})/, '$1.$2.$3');
                } else if (valor.length > 2) {
                    valor = valor.replace(/(\d{2})(\d{1,3})/, '$1.$2');
                }
            }
        }
        
        input.value = valor;
    }
    function validarDocumentoInput(input) {
        const valor = input.value.replace(/\D/g, '');
        const tipoUsuario = document.querySelector('input[name="tipo_usuario"]:checked');
        const infoDiv = document.getElementById('documentoInfo');
        
        if (!tipoUsuario) {
            infoDiv.innerHTML = 'Selecione o tipo de usuário';
            infoDiv.className = 'documento-info erro';
            return false;
        }

        let valido = false;
        let mensagem = '';

        if (tipoUsuario.value === 'usuario') {
            if (valor.length === 11) {
                valido = true;
                mensagem = '✓ CPF válido (11 dígitos)';
            } else if (valor.length >= 7 && valor.length <= 12) {
                valido = true;
                mensagem = '✓ RG válido (' + valor.length + ' dígitos)';
            } else {
                mensagem = '✗ Para usuário: CPF deve ter 11 dígitos ou RG 7-12 dígitos';
            }
        } else if (tipoUsuario.value === 'promotor') {
            if (valor.length === 14) {
                valido = true;
                mensagem = '✓ CNPJ válido (14 dígitos)';
            } else {
                mensagem = '✗ Para promotor: CNPJ deve ter 14 dígitos';
            }
        }

        infoDiv.innerHTML = mensagem;
        infoDiv.className = valido ? 'documento-info usuario' : 'documento-info erro';
        infoDiv.style.display = 'block';

        return valido;
    }
    document.getElementById('formCadastro').addEventListener('submit', function(e) {
        const documentoValido = validarDocumentoInput(document.getElementById('documento'));
        if (!documentoValido) {
            e.preventDefault();
            alert('Por favor, corrija o documento antes de enviar.');
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        atualizarInfoDocumento();
    });
    </script>
</body>
</html>
