<?php
session_start();
session_destroy();
header("Location: ../personel/login_personel.php");
exit;
