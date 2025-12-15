<?php
session_start();
require_once 'conexaobd.php';

if (!isset($_SESSION['logado']) || $_SESSION['tipo'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(16));
}

if (isset($_POST['remover_paciente']) && isset($_POST['token'])) {
    if (!isset($_SESSION['token']) || $_POST['token'] !== $_SESSION['token']) {
        $_SESSION['mensagem'] = "Acesso não autorizado.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $cpf_paciente = $_POST['remover_paciente'];

    $stmt_check = $conexao->prepare("
        SELECT ID_paciente, nome_paciente 
        FROM paciente 
        WHERE CPF_paciente = ? AND ID_usuario = ?
    ");
    $stmt_check->bind_param("si", $cpf_paciente, $_SESSION['ID_usuario']);
    $stmt_check->execute();
    $paciente = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($paciente) {
        $stmt_del_consultas = $conexao->prepare("
            DELETE c FROM consultas c
            INNER JOIN paciente p ON c.CPF_cliente = p.CPF_paciente
            WHERE p.CPF_paciente = ? AND p.ID_usuario = ?
        ");
        $stmt_del_consultas->bind_param("si", $cpf_paciente, $_SESSION['ID_usuario']);
        $stmt_del_consultas->execute();
        $consultas_removidas = $stmt_del_consultas->affected_rows;
        $stmt_del_consultas->close();

        $stmt_del = $conexao->prepare("DELETE FROM paciente WHERE CPF_paciente = ?");
        $stmt_del->bind_param("s", $cpf_paciente);
        $stmt_del->execute();
        $stmt_del->close();

        $msg = "Paciente <strong>" . htmlspecialchars($paciente['nome_paciente']) . "</strong> removido";
        if ($consultas_removidas > 0) {
            $msg .= " e suas $consultas_removidas consulta(s)";
        }
        $msg .= " com sucesso!";
        $_SESSION['mensagem'] = $msg;
    } else {
        $_SESSION['mensagem'] = "Paciente não encontrado ou acesso negado.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['remover_paciente'])) {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');

    $cpf_clean = preg_replace('/\D/', '', $cpf);

    if (!$nome || !$telefone || !$endereco || strlen($cpf_clean) !== 11) {
        $_SESSION['mensagem'] = "Preencha todos os campos. CPF deve ter 11 dígitos (apenas números).";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $stmt = $conexao->prepare("
        INSERT INTO paciente (nome_paciente, telefone_paciente, endereco_paciente, CPF_paciente, ID_usuario)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssi", $nome, $telefone, $endereco, $cpf_clean, $_SESSION['ID_usuario']);

    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Paciente cadastrado com sucesso!";
    } else {
        if ($stmt->errno == 1062 && strpos($stmt->error, 'CPF_paciente') !== false) {
            $_SESSION['mensagem'] = "CPF já cadastrado!";
        } else {
            $_SESSION['mensagem'] = "Erro: " . htmlspecialchars($stmt->error);
        }
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$stmt_sel = $conexao->prepare("SELECT * FROM paciente WHERE ID_usuario = ? ORDER BY nome_paciente ASC");
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
        .modal-header.bg-danger { color: white; }
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-<?= 
            strpos($_SESSION['mensagem']) !== false ? 'danger' : 
            (strpos($_SESSION['mensagem']) !== false ? 'warning' : 'success') 
        ?> alert-dismissible fade show">
            <?= $_SESSION['mensagem'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Painel de Agendamento</h2>
        <a href="frequencia.php" class="btn btn-outline-primary">Frequência</a>
        <a href="consultas.php" class="btn btn-outline-primary">Consultas</a>
        <a href="cadastrar_medico.php" class="btn btn-outline-success">Médicos</a>
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
                        <th>CPF</th>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Endereço</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['CPF_paciente']) ?></td>
                        <td><?= htmlspecialchars($p['nome_paciente']) ?></td>
                        <td><?= htmlspecialchars($p['telefone_paciente']) ?></td>
                        <td><?= htmlspecialchars($p['endereco_paciente']) ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#modalRemover<?= htmlspecialchars($p['CPF_paciente']) ?>">
                                🗑️ Remover
                            </button>

                            <div class="modal fade" id="modalRemover<?= htmlspecialchars($p['CPF_paciente']) ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title">Confirmar Exclusão</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Tem certeza que deseja remover o paciente abaixo?</p>
                                            <ul class="list-unstyled bg-light p-3 rounded">
                                                <li><strong>Nome:</strong> <?= htmlspecialchars($p['nome_paciente']) ?></li>
                                                <li><strong>CPF:</strong> <?= htmlspecialchars($p['CPF_paciente']) ?></li>
                                            </ul>
                                            <p class="text-danger fw-bold">
                                                Esta ação é <u>irreversível</u> e removerá também todas as consultas associadas.
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="remover_paciente" value="<?= htmlspecialchars($p['CPF_paciente']) ?>">
                                                <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                                                <button type="submit" class="btn btn-danger">Remover</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
