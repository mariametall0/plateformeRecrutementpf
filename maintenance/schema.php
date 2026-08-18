<?php
require_once "../config/database.php";
echo "--- MESSAGES_INTERNES ---\n";
print_r($pdo->query('SHOW COLUMNS FROM messages_internes')->fetchAll(PDO::FETCH_ASSOC));


