<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=plateforme_recrutement;charset=utf8mb4', 'root', '');
    $stmt = $pdo->query('SHOW TABLES');
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo $row[0] . PHP_EOL;
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
