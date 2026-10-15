<?php
$host = "localhost"; //127.0.0.1
$port = "5432";          // ako ti je lokalni PG na drugom portu, promijeni
$dbname = "postgres"; // naziv tvoje lokalne baze
$user = "postgres";      // ili tvoj lokalni user
$pass = YOUR_PASSWORD;

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // jer su tablice u shemi projekt
    $pdo->exec("SET search_path TO projekt");

} catch (PDOException $e) {
    die("Greška pri spajanju na bazu: " . $e->getMessage());
}