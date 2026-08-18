<?php
require 'config/database.php';
echo "--- CONCOURS ---\n";
print_r($pdo->query('DESCRIBE concours')->fetchAll(PDO::FETCH_ASSOC));
echo "--- CANDIDATURES ---\n";
print_r($pdo->query('DESCRIBE candidatures')->fetchAll(PDO::FETCH_ASSOC));
echo "--- OFFRES ---\n";
print_r($pdo->query('DESCRIBE offres')->fetchAll(PDO::FETCH_ASSOC));
?>










