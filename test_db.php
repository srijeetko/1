<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=alphanutrition_db", "root", "");
    echo "Connection successful!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
