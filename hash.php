<?php
// Ubah string di bawah menjadi password yang ingin di-hash
$password = 'admin';

// Tampilkan hasil hash password
echo password_hash($password, PASSWORD_DEFAULT);
