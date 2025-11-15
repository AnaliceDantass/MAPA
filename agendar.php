<?php
session_start();
require_once 'conexaobd.php';

if (!isset($_SESSION['logado']) || $_SESSION['tipo'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');

    if ($nome && $telefone && $endereco) {
        $stmt = $conexao->prepare("INSERT INTO paciente (nome_paciente, telefone_paciente, endereco_paciente) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nome, $telefone, $endereco);
        $stmt->execute();
        $stmt->close();
    }
}

$resultado = $conexao->query("SELECT * FROM paciente ORDER BY ID_paciente DESC");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Agendamentos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
        }
        form {
            margin-bottom: 30px;
        }
        table {
            border-collapse: collapse;
            width: 80%;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #eee;
        }
    </style>
</head>
<body>
    <h2>Painel de Agendamento</h2>

    <form method="post">
        <h3>Cadastrar novo paciente</h3>
        <input type="text" name="nome" placeholder="Nome completo" required>
        <input type="text" name="telefone" placeholder="Telefone" required>
        <input type="text" name="endereco" placeholder="Endereço" required>
        <button type="submit">Cadastrar</button>
    </form>

    <h3>Pacientes cadastrados</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Telefone</th>
            <th>Endereço</th>
        </tr>
        <?php while ($paciente = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($paciente['ID_paciente']) ?></td>
                <td><?= htmlspecialchars($paciente['nome_paciente']) ?></td>
                <td><?= htmlspecialchars($paciente['telefone_paciente']) ?></td>
                <td><?= htmlspecialchars($paciente['endereco_paciente']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <form method="post" action="sair.php">
        <button type="submit">Sair</button>
    </form>
</body>
</html>
