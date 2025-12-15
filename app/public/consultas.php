<?php
session_start();
require_once 'conexaobd.php';

if (!isset($_SESSION['logado']) || $_SESSION['tipo'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar_status') {
    $id_consulta = (int)($_POST['id_consulta'] ?? 0);
    $novo_status = $_POST['novo_status'] ?? 'Agendada';

    if ($id_consulta <= 0 || !in_array($novo_status, ['Agendada', 'Realizada', 'Cancelada'])) {
        $_SESSION['mensagem'] = "Dados inválidos para atualização.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $stmt_check = $conexao->prepare("
        SELECT c.ID_consulta 
        FROM consultas c
        INNER JOIN paciente p ON c.CPF_cliente = p.CPF_paciente
        WHERE c.ID_consulta = ? AND p.ID_usuario = ?
    ");
    $stmt_check->bind_param("ii", $id_consulta, $_SESSION['ID_usuario']);
    $stmt_check->execute();
    $existe = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$existe) {
        $_SESSION['mensagem'] = "Consulta não encontrada ou acesso negado.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $stmt_update = $conexao->prepare("
        UPDATE consultas 
        SET status_consulta = ? 
        WHERE ID_consulta = ?
    ");
    $stmt_update->bind_param("si", $novo_status, $id_consulta);
    
    if ($stmt_update->execute()) {
        $_SESSION['mensagem'] = "Status atualizado com sucesso!";
    } else {
        $_SESSION['mensagem'] = "Erro ao atualizar: " . htmlspecialchars($stmt_update->error, ENT_QUOTES, 'UTF-8');
    }
    $stmt_update->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') !== 'atualizar_status') {
    $cpf_paciente = $_POST['cpf_paciente'] ?? null;
    $crm_medico = $_POST['crm_medico'] ?? null;
    $data = trim($_POST['data'] ?? '');
    $horario = trim($_POST['horario'] ?? '');
    $status = $_POST['status'] ?? 'Agendada';

    if (!$cpf_paciente || !$crm_medico || !$data) {
        $_SESSION['mensagem'] = "Paciente, médico e data são obrigatórios.";
    } else {
        $data_valida = DateTime::createFromFormat('Y-m-d', $data);
        if (!$data_valida || $data_valida->format('Y-m-d') !== $data) {
            $_SESSION['mensagem'] = "Data inválida. Use o calendário.";
        } else {
            $stmt = $conexao->prepare("
                INSERT INTO consultas (CPF_cliente, CRM_medico, data_consulta, horario, status_consulta)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssss", $cpf_paciente, $crm_medico, $data, $horario, $status);

            if ($stmt->execute()) {
                $_SESSION['mensagem'] = "Consulta agendada com sucesso!";
            } else {
                if ($stmt->errno == 1062 && strpos($stmt->error, 'Duplicate entry') !== false) {
                    $_SESSION['mensagem'] = "Já existe uma consulta nesse horário para este paciente/médico.";
                } else {
                    $_SESSION['mensagem'] = "Erro ao agendar: " . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8');
                }
            }
            $stmt->close();
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$stmt_pacientes = $conexao->prepare("
    SELECT CPF_paciente, nome_paciente 
    FROM paciente 
    WHERE ID_usuario = ? 
    ORDER BY nome_paciente
");
$stmt_pacientes->bind_param("i", $_SESSION['ID_usuario']);
$stmt_pacientes->execute();
$result_pacientes = $stmt_pacientes->get_result();

$stmt_medicos = $conexao->prepare("SELECT CRM_medico, nome_medico FROM medico ORDER BY nome_medico");
$stmt_medicos->execute();
$result_medicos = $stmt_medicos->get_result();

$stmt_consultas = $conexao->prepare("
    SELECT 
        c.ID_consulta AS ID,
        p.nome_paciente,
        m.nome_medico,
        c.data_consulta,
        c.horario,
        c.status_consulta
    FROM consultas c
    INNER JOIN paciente p ON c.CPF_cliente = p.CPF_paciente
    INNER JOIN medico m ON c.CRM_medico = m.CRM_medico
    WHERE p.ID_usuario = ?
    ORDER BY c.data_consulta DESC, c.horario DESC
");
$stmt_consultas->bind_param("i", $_SESSION['ID_usuario']);
$stmt_consultas->execute();
$result_consultas = $stmt_consultas->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Consultas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .status-select { font-size: 0.9em; padding: 4px 8px; }
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($_SESSION['mensagem'])): 
        $msg = $_SESSION['mensagem'];
        $tipo = strpos($msg, 'Erro') !== false ? 'danger' : 'success';
    ?>
        <div class="alert alert-<?= $tipo ?> alert-dismissible fade show">
            <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Agendar Consulta</h2>
        <a href="agendar.php" class="btn btn-outline-secondary">◀️ Voltar</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="acao" value="agendar">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Paciente</label>
                        <select name="cpf_paciente" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php while ($p = $result_pacientes->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($p['CPF_paciente'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($p['nome_paciente'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Médico</label>
                        <select name="crm_medico" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php while ($m = $result_medicos->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($m['CRM_medico'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($m['nome_medico'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data</label>
                        <input type="date" name="data" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Horário</label>
                        <input type="time" name="horario" class="form-control" required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Agendar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <h3 class="mb-3">Consultas Agendadas</h3>
    <?php if ($result_consultas->num_rows === 0): ?>
        <div class="alert alert-info">Nenhuma consulta cadastrada ainda.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $result_consultas->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nome_paciente'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($c['nome_medico'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($c['data_consulta'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $c['horario'] ? htmlspecialchars(date('H:i', strtotime($c['horario'])), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="acao" value="atualizar_status">
                                    <input type="hidden" name="id_consulta" value="<?= (int)$c['ID'] ?>">
                                    <select name="novo_status" class="form-select form-select-sm status-select" onchange="this.form.submit()">
                                        <option value="Agendada" <?= $c['status_consulta'] === 'Agendada' ? 'selected' : '' ?>>Agendada</option>
                                        <option value="Realizada" <?= $c['status_consulta'] === 'Realizada' ? 'selected' : '' ?>>Realizada</option>
                                        <option value="Cancelada" <?= $c['status_consulta'] === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                    </select>
                                </form>
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
