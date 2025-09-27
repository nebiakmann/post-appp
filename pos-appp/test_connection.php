<?php
require 'db.php'; // db.php dosyanın doğru konumda olduğundan emin ol

try {
    $pdo = db(); // PDO singleton nesnesi

    echo "<h2>Veritabanı bağlantısı başarılı!</h2>";

    // Tabloları listele
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($tables) {
        echo "<h3>Mevcut tablolar:</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table, ENT_QUOTES, 'UTF-8') . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Henüz tablolar oluşturulmamış.</p>";
    }
} catch (PDOException $e) {
    echo "<h2>Veritabanı bağlantı hatası:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
}
?>
