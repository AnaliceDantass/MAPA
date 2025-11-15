<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAPA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        html, body { height: 100%; }
        body { min-height: 100vh; background-color: rgb(245, 245, 245); }

        .design-section {
            background: linear-gradient(135deg, rgb(0, 255, 229) 20%, rgb(0, 199, 192) 60%);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        @media (min-width: 768px) {
            .design-section { min-height: 100vh; }
        }

        .login-section {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            padding: 2.5rem;
            width: 100%;
            height: 55vh;
            max-width: 350px;
            margin: 2rem auto;
            text-align: center;
        }

        .btn.btn-primary {
            background-color: rgba(0, 201, 194, 1);
            margin-top: 3vh;
            width: 100px;
            height: 5vh;
            border-radius: 3vh;
            border: none;
            font-size: 1rem;
            font-weight: bold;
        }

        .mb-5 input {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            margin-top: 1vh;
            box-sizing: border-box;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }

        .col-12.col-md-6.design-section { 
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif; 
        }
        .mb-4 { font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif; }

        .msg-erro {
            color: #d9534f;
            background-color: #fdf2f2;
            border: 1px solid #eed3d7;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .login-section .alert { margin-bottom: 1.5rem; font-size: 0.95rem; }
    </style>
</head>
<body>