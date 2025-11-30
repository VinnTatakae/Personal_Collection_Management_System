<?php
session_start();
include './config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ? AND role = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = 'admin';
        header("Location: ../admin/dashboard.php");
        exit;
    } else {
        $error = "Invalid admin credentials!";
    }
}

?>
<form method="POST">
  <h3>Admin Login</h3>
  <input type="text" name="username" placeholder="Admin username" required><br>
  <input type="password" name="password" placeholder="Password" required><br>
  <button type="submit">Login</button>
</form>
<?php if(!empty($error)) echo $error; ?>
