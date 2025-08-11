<?php
require_once 'inc/db.php';

$nrp = '991122';
$passwordInput = 'cadangan123';

$stmt = $pdo->prepare("SELECT * FROM personel WHERE nrp = ?");
$stmt->execute([$nrp]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "❌ User dengan NRP $nrp tidak ditemukan.";
    exit;
}

echo "Hash di database: " . $user['password'] . "<br>";
echo "Password input: $passwordInput<br>";

if (password_verify($passwordInput, $user['password'])) {
    echo "✅ PASSWORD COCOK";
} else {
    echo "❌ PASSWORD TIDAK COCOK";
}
