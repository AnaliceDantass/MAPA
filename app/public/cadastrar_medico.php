<?php
session_start();
require_once 'conexaobd.php';

if (!isset($_SESSION['logado']) || $_SESSION['tipo'] !== 'Admin') {
    header('Location: agendar.php');
    exit;
}

if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['remover_medico'])) {
    $nome = trim($_POST['nome'] ?? '');
    $crm = trim($_POST['crm'] ?? '');
    $especialidade = trim($_POST['especialidade'] ?? '');

    if ($nome && $crm) {
        $stmt = $conexao->prepare("INSERT INTO medico (nome_medico, CRM_medico, especialidade_medico) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nome, $crm, $especialidade);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Médico cadastrado com sucesso!";
        } else {
            if ($stmt->errno == 1062 && strpos($stmt->error, 'CRM_medico') !== false) {
                $_SESSION['mensagem'] = "CRM já cadastrado!";
            } else {
                $_SESSION['mensagem'] = "Erro: " . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8');
            }
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['mensagem'] = "Nome e CRM são obrigatórios.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

if (isset($_POST['remover_medico']) && isset($_POST['token'])) {
    if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
        $_SESSION['mensagem'] = "Acesso não autorizado.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $id_medico = (int) $_POST['remover_medico'];

    $check = $conexao->prepare("SELECT nome_medico FROM medico WHERE ID_medico = ?");
    $check->bind_param("i", $id_medico);
    $check->execute();
    $medico = $check->get_result()->fetch_assoc();
    $check->close();

    if ($medico) {
        $stmt = $conexao->prepare("DELETE FROM medico WHERE ID_medico = ?");
        $stmt->bind_param("i", $id_medico);
        
        if ($stmt->execute()) {
            $nome = htmlspecialchars($medico['nome_medico'], ENT_QUOTES, 'UTF-8');
            $_SESSION['mensagem'] = "Médico $nome removido com sucesso!";
        } else {
            $_SESSION['mensagem'] = "Erro ao remover médico: " . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8');
        }
        $stmt->close();
    } else {
        $_SESSION['mensagem'] = "Médico não encontrado.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
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
    <style> 
        body { padding: 20px; background-color: #f8f9fa; } 
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($_SESSION['mensagem'])): 
        $msg = $_SESSION['mensagem'];
        $tipo = 'success';
        if (strpos($msg, 'Erro') !== false || strpos($msg, 'não autorizado') !== false || strpos($msg, 'não encontrado') !== false) {
            $tipo = 'danger';
        } elseif (strpos($msg, 'CRM já') !== false) {
            $tipo = 'warning';
        }
    ?>
        <div class="alert alert-<?= $tipo ?> alert-dismissible fade show">
            <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Cadastrar Médico</h2>
        <a href="agendar.php" class="btn btn-outline-secondary">◀️ Voltar</a>
    </div>

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

    <h3 class="mb-3">Médicos Cadastrados</h3>
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
                            <td><?= htmlspecialchars($m['CRM_medico'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($m['nome_medico'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($m['especialidade_medico'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalRemover<?= (int)$m['ID_medico'] ?>">
                                    🗑️ Remover
                                </button>

                                <div class="modal fade" id="modalRemover<?= (int)$m['ID_medico'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Confirmar Exclusão</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Tem certeza que deseja remover o médico abaixo?</p>
                                                <ul class="list-unstyled bg-light p-3 rounded">
                                                    <li><strong>Nome:</strong> <?= htmlspecialchars($m['nome_medico'], ENT_QUOTES, 'UTF-8') ?></li>
                                                    <li><strong>CRM:</strong> <?= htmlspecialchars($m['CRM_medico'], ENT_QUOTES, 'UTF-8') ?></li>
                                                </ul>
                                                <p class="text-danger fw-bold">
                                                    Esta ação é <u>irreversível</u>.
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="remover_medico" value="<?= (int)$m['ID_medico'] ?>">
                                                    <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token'], ENT_QUOTES, 'UTF-8') ?>">
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
