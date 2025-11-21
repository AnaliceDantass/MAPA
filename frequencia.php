<?php
session_start();
require_once 'conexaobd.php';

if (!isset($_SESSION['logado']) || $_SESSION['tipo'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

$sql = "SELECT 
            f.ID_frequencia, 
            p.nome_paciente, 
            c.data_consulta, 
            c.status_consulta
        FROM frequencia f
        JOIN paciente p ON f.ID_paciente = p.ID_paciente
        JOIN consultas c ON f.ID_consulta = c.ID_consulta
        ORDER BY p.nome_paciente ASC, c.data_consulta DESC";

$result = $conexao->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Frequência dos Pacientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 30px;
            font-family: Arial, sans-serif;
        }
        table {
            border-collapse: collapse;
            width: 90%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #eee;
        }
        .Agendada { background-color: #fff3cd; }   
        .Realizada { background-color: #d4edda; }  
        .Cancelada { background-color: #f8d7da; }  
    </style>
</head>
<body>

<h2>Frequência dos Pacientes</h2>

<table class="table">
    <tr>
        <th>ID</th>
        <th>Paciente</th>
        <th>Data</th>
        <th>Status</th>
    </tr>

    <?php while($row = $result->fetch_assoc()): 
        $status = $row['status_consulta']; 
    ?>
        <tr class="<?= $status ?>">
            <td><?= htmlspecialchars($row['ID_frequencia']) ?></td>
            <td><?= htmlspecialchars($row['nome_paciente']) ?></td>
            <td><?= date('d/m/Y', strtotime($row['data_consulta'])) ?></td>
            <td><?= htmlspecialchars($status) ?></td>
        </tr>
    <?php endwhile; ?>
</table>

<a href="agendar.php" class="btn btn-primary mt-3">Voltar ao Painel</a>

</body>
</html>
