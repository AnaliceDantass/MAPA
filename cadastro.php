<?php
session_start();

if ($_POST) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        header("Location: cadastro.php?erro=csrf_invalido");
        exit;
    }

    $nome_usuario = trim($_POST['nome_usuario'] ?? '');
    $email_usuario = trim($_POST['email_usuario'] ?? '');
    $senha_usuario = $_POST['senha_usuario'] ?? '';

    if (empty($nome_usuario) || empty($email_usuario) || empty($senha_usuario)) {
        header("Location: cadastro.php?erro=campos_vazios");
        exit;
    }

    if (!filter_var($email_usuario, FILTER_VALIDATE_EMAIL)) {
        header("Location: cadastro.php?erro=email_invalido");
        exit;
    }

    if (strlen($senha_usuario) < 8) {
        header("Location: cadastro.php?erro=senha_fraca");
        exit;
    }

    require_once 'conexao.php';

    $stmt_check = $conexao->prepare("SELECT id_usuario FROM usuario WHERE nome_usuario = ? OR email_usuario = ?");
    $stmt_check->bind_param("ss", $nome_usuario, $email_usuario);
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();

    if ($resultado->num_rows > 0) {
        header("Location: cadastro.php?erro=usuario_ou_email_existente");
        exit;
    }

    $senhaCriptografada = password_hash($senha_usuario, PASSWORD_DEFAULT);

    $stmt_insert = $conexao->prepare("INSERT INTO usuario (nome_usuario, senha_usuario, email_usuario) VALUES (?, ?, ?)");
    $stmt_insert->bind_param("sss", $nome_usuario, $senhaCriptografada, $email_usuario);

    if ($stmt_insert->execute()) {
        $mensagem = "1"; 
    } else {
        $mensagem = "0";
    }

    header("Location: index.php?mensagem=$mensagem");
    exit;
} else {
    header("Location: cadastro.php");
    exit;
}
?>