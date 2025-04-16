<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Signup | Nova View Hotel</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav id="navbar">
            <div class="container">
                <h1 class="logo"><a href="index.html">NVH</a></h1>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a class="current" href="loginsignup.php">Login/Signup</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="login-signup-container">
        <div class="form-box">
            <form action="login.php" method="post">
                <h2>Login</h2>
                <?php
                session_start();
                if (isset($_SESSION['error_message'])) {
                    echo '<p style="color: red;">' . $_SESSION['error_message'] . '</p>';
                    unset($_SESSION['error_message']);
                }
                ?>
                <input type="text" name="username" placeholder="Enter Username" required>
                <input type="password" name="password" placeholder="Enter Password" required>
                <button type="submit">Login</button>
                <p class="signup-link">Don't have an account? <a href="signuphtml.php"><span class="text-prim">Signup</span></a></p>
            </form>
        </div>
        <div id="adminbtn">
            <a class="btn" href="adminlogin.php">Login As Admin</a>
        </div>
    </div>

    <footer id="main-footer">
        <p>Nova View Hotel &copy; 2024, All Rights Reserved</p>
    </footer>
</body>
</html>
