<?php
include "assets/phpqrcode/qrlib.php"; // Make sure this path is correct

$tanggal = date('Ymd');
$data = "ABSEN|$tanggal";

// Output langsung QR image PNG
header('Content-Type: image/png');
QRcode::png($data, false, 'QR_ECLEVEL_L', 4);
