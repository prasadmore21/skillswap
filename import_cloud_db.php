<?php
// One-time importer script for Aiven MySQL
echo "=== SkillSwap Cloud DB Importer ===\n";

$host = 'mysql-cd3bb31-skillswapconnect.g.aivencloud.com';
$port = 21010;
$user = 'avnadmin';
$dbname = 'defaultdb';

echo "Enter your Aiven MySQL password: ";
$pass = trim(fgets(STDIN));

if (empty($pass)) {
    die("Password cannot be empty.\n");
}

echo "\nConnecting to Aiven MySQL ($host:$port)...\n";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);
    echo "Connected successfully!\n";

    echo "Reading schema.sql...\n";
    $sql = file_get_contents(__DIR__ . '/sql/schema.sql');

    echo "Importing tables and seed data...\n";
    $pdo->exec($sql);

    echo "\n SUCCESS! All tables and seed data have been imported into Aiven MySQL!\n";
} catch (Exception $e) {
    echo "\n ERROR: " . $e->getMessage() . "\n";
}
