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
        SUM(CASE WHEN c.status_consulta = 'Realizada' THEN 1 ELSE 0 END) AS sessoes_realizadas,
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
        body { padding: 20px; background-color: #f8f9fa; }
        .card-header { font-weight: bold; }
        .progress { height: 8px; }
        .progress-bar { font-size: 0.75rem; }
        .text-realizada { color: #198754; font-weight: bold; }
        .text-agendada { color: #ffc107; font-weight: bold; }
        .text-pendente { color: #6c757d; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Frequência dos Pacientes</h2>
        <a href="agendar.php" class="btn btn-outline-secondary">◀️ Voltar</a>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="alert alert-info">
            Nenhum paciente com sessões cadastradas ainda.<br>
            <small>Agende consultas em <a href="consultas.php">Consultas</a> para ver a frequência aqui.</small>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php while ($p = $result->fetch_assoc()): 
                $total = (int)$p['total_sessoes'];
                $realizadas = (int)$p['sessoes_realizadas'];
                $faltam = max(0, $total - $realizadas);
                $progresso = $total > 0 ? round(($realizadas / $total) * 100) : 0;
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <?= htmlspecialchars($p['nome_paciente']) ?>
                        </div>
                        <div class="card-body">
                            <p class="mb-1">
                                <span class="text-realizada">Realizadas:</span> <strong><?= $realizadas ?></strong>
                            </p>
                            <p class="mb-1">
                                <span class="text-agendada">Agendadas:</span> <strong><?= $faltam ?></strong>
                            </p>
                            <p class="mb-2">
                                <span class="fw-bold">Total:</span> <strong><?= $total ?></strong> sessões
                            </p>

                            <?php if ($total > 0): ?>
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-success" style="width: <?= $progresso ?>%">
                                        <?= $progresso ?>%
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($p['proxima_sessao']): ?>
                                <p class="mb-0">
                                    <span class="text-agendada">Próxima:</span> 
                                    <strong><?= date('d/m/Y', strtotime($p['proxima_sessao'])) ?></strong>
                                </p>
                            <?php else: ?>
                                <p class="mb-0 text-pendente">Sem sessões agendadas</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <div class="mt-4 p-3 bg-light rounded">
        <h5>Como funciona?</h5>
        <ul>
            <li>A frequência é calculada com base nas <strong>consultas</strong> do paciente.</li>
            <li>Mude o status da consulta para <strong>Realizada</strong> em <a href="consultas.php">Consultas</a> para atualizar aqui.</li>
            <li>Não é necessário cadastrar "frequência" manualmente.</li>
        </ul>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
