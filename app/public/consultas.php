<?php
session_start();
require_once 'conexaobd.php';

if (!isset($_SESSION['logado']) || $_SESSION['tipo'] !== 'Admin') {
    header('Location: agendar.php');
    exit;
}
if (!isset($_SESSION['ID_usuario'])) {
    die("<h3>❌ Sessão incompleta</h3><p>Faça login novamente. O sistema não recebeu seu ID.</p>");
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf_paciente = $_POST['cpf_paciente'] ?? null;
    $crm_medico = $_POST['crm_medico'] ?? null;
    $data = trim($_POST['data'] ?? '');
    $horario = trim($_POST['horario'] ?? '');
    $status = $_POST['status'] ?? 'Agendada';
    $obs = trim($_POST['observacoes'] ?? ''); 

    if (!$cpf_paciente || !$crm_medico || !$data) {
        $_SESSION['mensagem'] = "❌ Paciente, médico e data são obrigatórios.";
    } else {
        $data_valida = DateTime::createFromFormat('Y-m-d', $data);
        if (!$data_valida || $data_valida->format('Y-m-d') !== $data) {
            $_SESSION['mensagem'] = "❌ Data inválida. Use o calendário.";
        } else {
            $stmt = $conexao->prepare("
                INSERT INTO consultas (CPF_paciente, CRM_medico, data_consulta, horario, status_consulta)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssss", $cpf_paciente, $crm_medico, $data, $horario, $status);

            if ($stmt->execute()) {
                $_SESSION['mensagem'] = "✅ Consulta agendada com sucesso!";
            } else {
                $_SESSION['mensagem'] = "❌ Erro ao agendar: " . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$stmt_consultas = $conexao->prepare("
    SELECT 
        c.ID_consulta,
        p.nome_paciente,
        p.CPF_paciente,
        m.nome_medico,
        c.data_consulta,
        c.horario,
        c.status_consulta
    FROM consultas c
    INNER JOIN paciente p ON c.CPF_paciente = p.CPF_paciente
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
    <style> body { padding: 20px; background-color: #f8f9fa; } </style>
</head>
<body>
<div class="container">
    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-<?= strpos($_SESSION['mensagem'], '❌') !== false ? 'danger' : 'success' ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['mensagem']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📅 Agendar Consulta</h2>
        <a href="agendar.php" class="btn btn-outline-secondary">👥 Pacientes</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Paciente</label>
                        <select name="cpf_paciente" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php while ($p = $result_pacientes->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($p['CPF_paciente']) ?>">
                                    <?= htmlspecialchars($p['nome_paciente']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Médico</label>
                        <select name="crm_medico" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php while ($m = $result_medicos->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($m['CRM_medico']) ?>">
                                    <?= htmlspecialchars($m['nome_medico']) ?>
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
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Agendada">Agendada</option>
                            <option value="Realizada">Realizada</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <h3 class="mb-3">📋 Consultas Agendadas</h3>
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
                            <td><?= htmlspecialchars($c['nome_paciente']) ?></td>
                            <td><?= htmlspecialchars($c['nome_medico']) ?></td>
                            <td><?= date('d/m/Y', strtotime($c['data_consulta'])) ?></td>
                            <td><?= $c['horario'] ? date('H:i', strtotime($c['horario'])) : '—' ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $c['status_consulta'] === 'Realizada' ? 'success' : 
                                    ($c['status_consulta'] === 'Cancelada' ? 'secondary' : 'warning')
                                ?>">
                                    <?= htmlspecialchars($c['status_consulta']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <form method="post" action="sair.php" class="mt-3">
        <button type="submit" class="btn btn-outline-secondary">🚪 Sair</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
