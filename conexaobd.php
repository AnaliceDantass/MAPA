<?php
  $conexao = mysqli_connect('localhost', 'root', '', 'MAPA');
  if (!$conexao) {  
    die("Falha na conexao com o banco de dados: " . mysqli_connect_error());  
  }