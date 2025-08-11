<?php
session_start();
// Fix: Ganti kondisi pengecekan session yang benar
if (isset($_SESSION['personel_id'])) {
    header('Location: scan.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIAPERS - Aplikasi Absensi Personel</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg,rgb(90, 119, 248) 0%,rgb(32, 65, 255) 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .login-header {
      text-align: center;
      margin-bottom: 40px;
      color: white;
    }

    .login-title {
      font-size: 4rem;
      font-weight: 900;
      letter-spacing: 2px;
      margin-bottom: 10px;
      text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .login-subtitle {
      font-size: 1.1rem;
      font-weight: 400;
      letter-spacing: 1px;
      opacity: 0.9;
    }

    .login-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
      padding: 40px 30px;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .error-message {
      background: #fee;
      color: #c53030;
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
      border: 1px solid #fed7d7;
      text-align: center;
      animation: shake 0.5s ease-in-out;
    }

    .input-group {
      margin-bottom: 25px;
    }

    .input-group input {
      width: 100%;
      padding: 16px 20px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 500;
      transition: all 0.3s ease;
      background: #f8fafc;
      color: #2d3748;
    }

    .input-group input:focus {
      outline: none;
      border-color: #667eea;
      background: white;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      transform: translateY(-1px);
    }

    .input-group input::placeholder {
      color: #a0aec0;
      font-weight: 400;
    }

    .login-button {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, #22c55e, #16a34a);
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 700;
      letter-spacing: 1px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-transform: uppercase;
      box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
    }

    .login-button:hover {
      background: linear-gradient(135deg, #16a34a, #15803d);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
    }

    .login-button:active {
      transform: translateY(0);
    }

    /* Responsive design */
    @media (max-width: 480px) {
      .login-title {
        font-size: 3rem;
      }
      
      .login-container {
        padding: 30px 25px;
      }
      
      body {
        padding: 15px;
      }
    }

    /* Animation for container */
    .login-container {
      animation: slideUp 0.6s ease-out;
    }

    .login-header {
      animation: fadeIn 0.8s ease-out;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
  </style>
</head>
<body>
  <div class="login-header">
    <h1 class="login-title">SIAPERS</h1>
    <p class="login-subtitle">APLIKASI ABSENSI PERSONEL</p>
  </div>

  <div class="login-container">
    <?php
    // Fix: Tampilkan error message jika ada
    if (isset($_GET['error'])) {
        echo '<div class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
    }
    ?>
    
    <!-- Fix: Tambahkan action ke proses_login.php -->
    <form method="POST" action="proses_login.php">
      <div class="input-group">
        <input type="text" id="nrp" name="nrp" placeholder="NRP" required 
               value="<?= isset($_POST['nrp']) ? htmlspecialchars($_POST['nrp']) : '' ?>">
      </div>
      
      <div class="input-group">
        <input type="password" id="password" name="password" placeholder="KATA SANDI" required>
      </div>
      
      <button type="submit" class="login-button">MASUK</button>
    </form>
  </div>

  <script>
    // Add interactive effects
    document.querySelectorAll('input').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
      });
      
      input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
      });
    });

    // Focus on first input field
    document.getElementById('nrp').focus();
  </script>
</body>
</html>