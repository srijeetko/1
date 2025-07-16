<?php
session_start();
include '../includes/db_connection.php';

// Check if already logged in
if(isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        // Fetch user by email
        $stmt = $pdo->prepare('SELECT admin_id, password_hash FROM admin_users WHERE email = ?');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        // Verify password - check both hashed and plain text for backward compatibility
        if ($admin && (password_verify($password, $admin['password_hash']) || $password === $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Alpha Nutrition</title>
    <style>
    .form {
      display: flex;
      flex-direction: column;
      gap: 10px;
      background-color: #ffffff;
      padding: 30px;
      width: 450px;
      border-radius: 20px;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
      margin: 301px auto;
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }
    ::placeholder {
      font-family: inherit;
    }
    .form button {
      align-self: flex-end;
    }
    .flex-column > label {
      color: #151717;
      font-weight: 600;
    }
    .inputForm {
      border: 1.5px solid #ecedec;
      border-radius: 10px;
      height: 50px;
      display: flex;
      align-items: center;
      padding-left: 10px;
      transition: 0.2s ease-in-out;
    }
    .input {
      margin-left: 10px;
      border-radius: 10px;
      border: none;
      width: 100%;
      height: 100%;
    }
    .input:focus {
      outline: none;
    }
    .inputForm:focus-within {
      border: 1.5px solid #2d79f3;
    }
    .flex-row {
      display: flex;
      flex-direction: row;
      align-items: center;
      gap: 10px;
      justify-content: space-between;
    }
    .flex-row > div > label {
      font-size: 14px;
      color: black;
      font-weight: 400;
    }
    .span {
      font-size: 14px;
      margin-left: 5px;
      color: #2d79f3;
      font-weight: 500;
      cursor: pointer;
    }
    .button-submit {
      margin: 20px 0 10px 0;
      background-color: #151717;
      border: none;
      color: white;
      font-size: 15px;
      font-weight: 500;
      border-radius: 10px;
      height: 50px;
      width: 100%;
      cursor: pointer;
    }
    .p {
      text-align: center;
      color: black;
      font-size: 14px;
      margin: 5px 0;
    }
    .btn {
      margin-top: 10px;
      width: 100%;
      height: 50px;
      border-radius: 10px;
      display: flex;
      justify-content: center;
      align-items: center;
      font-weight: 500;
      gap: 10px;
      border: 1px solid #ededef;
      background-color: white;
      cursor: pointer;
      transition: 0.2s ease-in-out;
    }
    .btn:hover {
      border: 1px solid #2d79f3;
    }
    .login-error {
      color: #d32f2f;
      background: #fff0f0;
      border: 1px solid #ffcdd2;
      padding: 10px 14px;
      border-radius: 4px;
      margin-bottom: 18px;
      font-size: 1rem;
      text-align: center;
    }
    </style>
</head>
<body>
<form class="form" method="POST">
    <?php if ($error): ?>
        <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div class="flex-column">
      <label for="email">Email</label>
    </div>
    <div class="inputForm">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" viewBox="0 0 32 32" height="20"><g data-name="Layer 3" id="Layer_3"><path d="m30.853 13.87a15 15 0 0 0 -29.729 4.082 15.1 15.1 0 0 0 12.876 12.918 15.6 15.6 0 0 0 2.016.13 14.85 14.85 0 0 0 7.715-2.145 1 1 0 1 0 -1.031-1.711 13.007 13.007 0 1 1 5.458-6.529 2.149 2.149 0 0 1 -4.158-.759v-10.856a1 1 0 0 0 -2 0v1.726a8 8 0 1 0 .2 10.325 4.135 4.135 0 0 0 7.83.274 15.2 15.2 0 0 0 .823-7.455zm-14.853 8.13a6 6 0 1 1 6-6 6.006 6.006 0 0 1 -6 6z"></path></g></svg>
      <input placeholder="Enter your Email" class="input" type="text" id="email" name="email" required>
    </div>
    <div class="flex-column">
      <label for="password">Password</label>
    </div>
    <div class="inputForm">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" viewBox="-64 0 512 512" height="20"><path d="m336 512h-288c-26.453125 0-48-21.523438-48-48v-224c0-26.476562 21.546875-48 48-48h288c26.453125 0 48 21.523438 48 48v224c0 26.476562-21.546875 48-48 48zm-288-288c-8.8125 0-16 7.167969-16 16v224c0 8.832031 7.1875 16 16 16h288c8.8125 0 16-7.167969 16-16v-224c0-8.832031-7.1875-16-16-16zm0 0"></path><path d="m304 224c-8.832031 0-16-7.167969-16-16v-80c0-52.929688-43.070312-96-96-96s-96 43.070312-96 96v80c0 8.832031-7.167969 16-16 16s-16-7.167969-16-16v-80c0-70.59375 57.40625-128 128-128s128 57.40625 128 128v80c0 8.832031-7.167969 16-16 16zm0 0"></path></svg>
      <input placeholder="Enter your Password" class="input" type="password" id="password" name="password" required>
    </div>
    <div class="flex-row">
      <div>
        <input type="checkbox" id="remember" name="remember">
        <label for="remember">Remember me</label>
      </div>
      <span class="span" onclick="alert('Forgot password functionality coming soon!')">Forgot password?</span>
    </div>
    <button class="button-submit" type="submit">Login</button>
</form>
</body>
</html>
