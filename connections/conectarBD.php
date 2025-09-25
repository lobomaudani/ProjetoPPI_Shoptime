<?php
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $dbname     = "ppi_v01_showtime";
    try {
        $conexao = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Falha na conexão: " . $e->getMessage()); 
    }
?>