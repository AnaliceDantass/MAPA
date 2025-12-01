<?php
session_start();
require_once 'conexaobd.php';

if (!isset($_SESSION['logado']) || $_SESSION['tipo'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

if (isset($_POST['remover_id']) && is_numeric($_POST['remover_id'])) {
    $id_paciente = (int) $_POST['remover_id'];

    $stmt_check = $conexao->prepare("SELECT ID_paciente FROM paciente WHERE ID_paciente = ? AND ID_usuario = ?");
    $stmt_check->bind_param("ii", $id_paciente, $_SESSION['ID_usuario']);
    $stmt_check->execute();
    $existe = $stmt_check->get_result()->num_rows > 0;
    $stmt_check->close();

    if ($existe) {
        $stmt_del = $conexao->prepare("DELETE FROM paciente WHERE ID_paciente = ?");
        $stmt_del->bind_param("i", $id_paciente);
        $stmt_del->execute();
        $stmt_del->close();
        $_SESSION['mensagem'] = "✅ Paciente removido com sucesso!";
    } else {
        $_SESSION['mensagem'] = "❌ Erro: Paciente não encontrado ou não autorizado.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['remover_id'])) {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');

    $cpf_clean = preg_replace('/\D/', '', $cpf);

    if (!$nome || !$telefone || !$endereco || strlen($cpf_clean) !== 11) {
        $_SESSION['mensagem'] = "❌ Preencha todos os campos. CPF deve ter 11 dígitos (apenas números).";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $stmt = $conexao->prepare("
        INSERT INTO paciente (nome_paciente, telefone_paciente, endereco_paciente, CPF_paciente, ID_usuario)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssi", $nome, $telefone, $endereco, $cpf_clean, $_SESSION['ID_usuario']);

    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "✅ Paciente cadastrado com sucesso!";
    } else {
        if ($stmt->errno == 1062 && strpos($stmt->error, 'CPF_paciente') !== false) {
            $_SESSION['mensagem'] = "⚠️ CPF já cadastrado! Verifique se o paciente já existe.";
        } else {
            $_SESSION['mensagem'] = "❌ Erro ao cadastrar: " . htmlspecialchars($stmt->error);
        }
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$stmt_sel = $conexao->prepare("SELECT * FROM paciente WHERE ID_usuario = ? ORDER BY ID_paciente DESC");
$stmt_sel->bind_param("i", $_SESSION['ID_usuario']);
$stmt_sel->execute();
$resultado = $stmt_sel->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .form-paciente { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-<?= strpos($_SESSION['mensagem'], '❌') !== false ? 'danger' : (strpos($_SESSION['mensagem'], '⚠️') !== false ? 'warning' : 'success') ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['mensagem']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📋 Painel de Agendamento</h2>
        <a href="frequencia.php" class="btn btn-outline-primary">📊 Frequência</a>
    </div>

    <div class="form-paciente mb-4">
        <h4 class="mb-3">➕ Novo Paciente</h4>
        <form method="post">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="nome" class="form-control" placeholder="Nome completo" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="telefone" class="form-control" placeholder="Telefone" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="endereco" class="form-control" placeholder="Endereço" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="cpf" class="form-control" placeholder="CPF" maxlength="14" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Cadastrar</button>
                </div>
            </div>
        </form>
    </div>

    <h4 class="mb-3">Pacientes Cadastrados</h4>
    <?php if ($resultado->num_rows === 0): ?>
        <div class="alert alert-info">Nenhum paciente cadastrado.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Endereço</th>
                        <th>CPF</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['ID_paciente']) ?></td>
                            <td><?= htmlspecialchars($p['nome_paciente']) ?></td>
                            <td><?= htmlspecialchars($p['telefone_paciente']) ?></td>
                            <td><?= htmlspecialchars($p['endereco_paciente']) ?></td>
                            <td><?= htmlspecialchars($p['CPF_paciente']) ?></td>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Remover?')">
                                    <input type="hidden" name="remover_id" value="<?= $p['ID_paciente'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <form method="post" action="sair.php" class="mt-3">
        <button type="submit" class="btn btn-secondary">🚪 Sair</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
