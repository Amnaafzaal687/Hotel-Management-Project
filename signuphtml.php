<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Nova View Hotel</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav id="navbar">
            <div class="container">
                <h1 class="logo"><a href="index.html">NVH</a></h1>
                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a class="current" href="loginsignup.php">Login/Signup</a></li> <!-- Updated link to PHP file for consistency -->
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="signup-container">
        <div class="signup-form-box">
            <h2>Signup</h2>
            <form action="signup.php" method="post">
                <?php
                session_start();
                if (isset($_SESSION['error_message'])) {
                    echo '<p style="color: red;">' . $_SESSION['error_message'] . '</p>';
                    unset($_SESSION['error_message']);
                }
                if (isset($_SESSION['username_err'])) echo '<p style="color: red;">' . $_SESSION['username_err'] . '</p>';
                if (isset($_SESSION['password_err'])) echo '<p style="color: red;">' . $_SESSION['password_err'] . '</p>';
                if (isset($_SESSION['confirm_password_err'])) echo '<p style="color: red;">' . $_SESSION['confirm_password_err'] . '</p>';
                if (isset($_SESSION['email_err'])) echo '<p style="color: red;">' . $_SESSION['email_err'] . '</p>';
                ?>
                <label for="firstName">First Name:</label>
                <input type="text" id="firstName" name="firstName" required>
                
                <label for="lastName">Last Name:</label>
                <input type="text" id="lastName" name="lastName" required>
                
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" required>
                
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" required>
                
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                
                <label for="confirm-password">Confirm Password:</label>
                <input type="password" id="confirm-password" name="confirm-password" required>
                
                <button type="submit">Sign Up</button>
            </form>
        </div>
    </div>

    <footer id="main-footer">
        <p>Nova View Hotel &copy; 2024, All Rights Reserved</p>
    </footer>
</body>
</html>
