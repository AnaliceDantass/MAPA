<?php
session_start();
require_once 'conexaobd.php';

if (!isset($_SESSION['logado']) || $_SESSION['tipo'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}

$sql = "
    SELECT 
        p.ID_paciente,
        p.nome_paciente,
        COUNT(c.ID_consulta) AS total_sessoes,
        SUM(CASE WHEN c.status_consulta = 'Realizada' THEN 1 ELSE 0 END) AS realizadas,
        SUM(CASE WHEN c.status_consulta = 'Ausente' THEN 1 ELSE 0 END) AS ausentes,
        SUM(CASE WHEN c.status_consulta = 'Agendada' THEN 1 ELSE 0 END) AS agendadas,
        MIN(CASE WHEN c.status_consulta = 'Agendada' THEN c.data_consulta END) AS proxima_sessao
    FROM paciente p
    LEFT JOIN consultas c ON p.CPF_paciente = c.CPF_cliente
    WHERE p.ID_usuario = ?
    GROUP BY p.ID_paciente, p.nome_paciente
    ORDER BY p.nome_paciente ASC
";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $_SESSION['ID_usuario']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frequência dos Pacientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            padding: 20px; 
            background-color: #f8f9fa; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-header {
            font-weight: bold;
        }
        .progress {
            height: 8px;
            margin-top: 4px;
            margin-bottom: 8px;
        }
        .status-badge {
            padding: 0.35em 0.6em;
            border-radius: 0.25rem;
            font-size: 0.85em;
            font-weight: 600;
        }
        .badge-realizada { background-color: #198754; color: white; }
        .badge-ausente { background-color: #dc3545; color: white; }
        .badge-agendada { background-color: #ffc107; color: #212529; }
        .badge-total { background-color: #6c757d; color: white; }
        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            margin-right: 6px;
        }
        .info-icon {
            font-size: 0.9em;
            opacity: 0.7;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Frequência dos Pacientes</h2>
            <p class="text-muted mb-0">
                <small>
                     Pacientes com sessões marcadas | 
                     Atualize o status em <a href="consultas.php">Consultas</a>
                </small>
            </p>
        </div>
        <a href="agendar.php" class="btn btn-outline-secondary">◀️ Voltar</a>
    </div>

    <!-- Legenda -->
    <div class="d-flex flex-wrap mb-3 p-2 bg-light rounded">
        <div class="legend-item">
            <span class="legend-color bg-success"></span>
            <span>Realizada</span>
        </div>
        <div class="legend-item">
            <span class="legend-color bg-danger"></span>
            <span>Ausente</span>
        </div>
        <div class="legend-item">
            <span class="legend-color" style="background-color: #ffc107;"></span>
            <span>Agendada</span>
        </div>
        <div class="legend-item">
            <span class="legend-color bg-secondary"></span>
            <span>Total</span>
        </div>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="alert alert-info">
            <h5>Nenhum paciente com sessões registradas.</h5>
            <p>
                Agende consultas em <a href="consultas.php">Consultas</a> para acompanhar a frequência aqui.<br>
                <small class="text-muted">Pacientes sem consultas não aparecem nesta lista.</small>
            </p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php while ($p = $result->fetch_assoc()): 
                $total = (int)$p['total_sessoes'];
                $realizadas = (int)$p['realizadas'];
                $ausentes = (int)$p['ausentes'];
                $agendadas = (int)$p['agendadas'];
                $progresso = $total > 0 ? round(($realizadas / $total) * 100) : 0;
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($p['nome_paciente']) ?>
                            <span class="badge bg-light text-dark"><?= $total ?> sessões</span>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="status-badge badge-realizada"><?= $realizadas ?> Realizada<?= $realizadas !== 1 ? 's' : '' ?></span>
                                <span class="status-badge badge-ausente"><?= $ausentes ?> Ausente<?= $ausentes !== 1 ? 's' : '' ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="status-badge badge-agendada"><?= $agendadas ?> Agendada<?= $agendadas !== 1 ? 's' : '' ?></span>
                                <?php if ($total > 0): ?>
                                    <span class="status-badge badge-total"><?= $progresso ?>%</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($total > 0): ?>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: <?= $progresso ?>%">
                                        <?= $progresso ?>%
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($p['proxima_sessao']): ?>
                                <div class="mt-3 small">
                                    <span class="text-warning">Próxima sessão:</span><br>
                                    <strong><?= date('d/m/Y', strtotime($p['proxima_sessao'])) ?></strong>
                                </div>
                            <?php elseif ($agendadas === 0 && $total > 0): ?>
                                <div class="mt-3 small text-muted">
                                    ⏳ Sem sessões agendadas<br>
                                    <small>Agende em <a href="consultas.php">Consultas</a></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
