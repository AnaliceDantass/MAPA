<?php
session_start();
require_once 'conexaobd.php';

if (!isset($_SESSION['logado']) || $_SESSION['tipo'] !== 'Admin') {
    header('Location: agendar.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $crm = trim($_POST['crm'] ?? '');
    $especialidade = trim($_POST['especialidade'] ?? '');

    if ($nome && $crm) {
        // ✅ Variáveis corretas + sem ID_medico no INSERT
        $stmt = $conexao->prepare("INSERT INTO medico (nome_medico, CRM_medico, especialidade_medico) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nome, $crm, $especialidade); // ← $nome, $crm, $especialidade

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "✅ Médico cadastrado com sucesso!";
        } else {
            if ($stmt->errno == 1062 && strpos($stmt->error, 'CRM_medico') !== false) {
                $_SESSION['mensagem'] = "⚠️ CRM já cadastrado!";
            } else {
                $_SESSION['mensagem'] = "❌ Erro: " . htmlspecialchars($stmt->error);
            }
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['mensagem'] = "❌ Nome e CRM são obrigatórios.";
    }
}

// ✅ Remover médico (protegido por POST e token)
if (isset($_POST['remover_medico']) && isset($_POST['token'])) {
    // Validação básica de token (evita CSRF simples)
    if (!isset($_SESSION['token']) || $_POST['token'] !== $_SESSION['token']) {
        $_SESSION['mensagem'] = "❌ Acesso não autorizado.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $id_medico = (int) $_POST['remover_medico'];

    // Verifica se o médico existe (opcional, mas bom pra UX)
    $check = $conexao->prepare("SELECT nome_medico FROM medico WHERE ID_medico = ?");
    $check->bind_param("i", $id_medico);
    $check->execute();
    $medico = $check->get_result()->fetch_assoc();

    if ($medico) {
        $stmt = $conexao->prepare("DELETE FROM medico WHERE ID_medico = ?");
        $stmt->bind_param("i", $id_medico);
        
        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "✅ Médico " . htmlspecialchars($medico['nome_medico']) . " removido com sucesso!";
        } else {
            $_SESSION['mensagem'] = "❌ Erro ao remover médico: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $_SESSION['mensagem'] = "❌ Médico não encontrado.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Gera token de segurança para formulários (uma vez por sessão)
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(16));
}

$resultado = $conexao->query("SELECT * FROM medico ORDER BY nome_medico");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Médico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> body { padding: 20px; background-color: #f8f9fa; } </style>
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
        <h2>👨‍⚕️ Cadastrar Médico</h2>
        <a href="agendar.php" class="btn btn-outline-secondary">◀️ Voltar</a>
    </div>

    <!-- Formulário -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="nome" class="form-control" placeholder="Nome do médico" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="crm" class="form-control" placeholder="CRM (ex: 12345/SP)" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="especialidade" class="form-control" placeholder="Especialidade">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100">Cadastrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<!-- Lista -->
<h3 class="mb-3">📋 Médicos Cadastrados</h3>
<?php if ($resultado->num_rows === 0): ?>
    <div class="alert alert-info">Nenhum médico cadastrado ainda.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>CRM</th>
                    <th>Nome</th>
                    <th>Especialidade</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($m = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['CRM_medico']) ?></td>
                        <td><?= htmlspecialchars($m['nome_medico']) ?></td>
                        <td><?= htmlspecialchars($m['especialidade_medico']) ?></td>
                        <td class="text-center">
                            <!-- Botão Remover -->
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#modalRemover<?= $m['ID_medico'] ?>">
                                🗑️ Remover
                            </button>

                            <!-- Modal de Confirmação -->
                            <div class="modal fade" id="modalRemover<?= $m['ID_medico'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title">⚠️ Confirmar Exclusão</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Tem certeza que deseja remover o médico abaixo?</p>
                                            <ul class="list-unstyled bg-light p-3 rounded">
                                                <li><strong>Nome:</strong> <?= htmlspecialchars($m['nome_medico']) ?></li>
                                                <li><strong>CRM:</strong> <?= htmlspecialchars($m['CRM_medico']) ?></li>
                                            </ul>
                                            <p class="text-danger fw-bold">
                                                ⚠️ Esta ação é <u>irreversível</u>.
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="remover_medico" value="<?= $m['ID_medico'] ?>">
                                                <input type="hidden" name="token" value="<?= $_SESSION['token'] ?? bin2hex(random_bytes(16)) ?>">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
