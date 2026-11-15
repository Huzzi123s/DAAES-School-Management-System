
<?php

session_start();

date_default_timezone_set('Asia/Karachi');

$base_url = '/DAAES_School_Management';

// Already logged-in admin
if (!empty($_SESSION['admin_id'])) {
    header("Location: {$base_url}/admin/dashboard.php");
    exit;
}

if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Login | DAAES School</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    background:
        radial-gradient(circle at 15% 20%, rgba(212,175,55,.13), transparent 30%),
        radial-gradient(circle at 85% 80%, rgba(32,83,135,.35), transparent 35%),
        #061326;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 25px;
    overflow-x: hidden;
}

.login-wrapper {
    width: 100%;
    max-width: 1050px;
    min-height: 620px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: #fff;
    border-radius: 25px;
    overflow: hidden;
    box-shadow: 0 30px 90px rgba(0,0,0,.35);
    animation: appear .7s ease;
}

@keyframes appear {
    from {
        opacity: 0;
        transform: translateY(25px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* LEFT PANEL */

.login-brand {
    position: relative;
    padding: 55px 45px;
    background: linear-gradient(145deg, #07182f, #102f52);
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
}

.login-brand::before,
.login-brand::after {
    content: "";
    position: absolute;
    border: 1px solid rgba(212,175,55,.2);
    border-radius: 50%;
}

.login-brand::before {
    width: 380px;
    height: 380px;
    right: -180px;
    top: -100px;
}

.login-brand::after {
    width: 300px;
    height: 300px;
    left: -170px;
    bottom: -120px;
}

.brand-content,
.brand-footer {
    position: relative;
    z-index: 1;
}

.brand-logo {
    width: 115px;
    height: 115px;
    background: white;
    border: 4px solid #d4af37;
    border-radius: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-bottom: 35px;
    box-shadow: 0 12px 30px rgba(0,0,0,.2);
}

.brand-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.brand-tag {
    color: #e2c66f;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 3px;
    margin-bottom: 15px;
}

.brand-content h1 {
    font-size: 34px;
    line-height: 1.25;
    font-weight: 800;
    margin-bottom: 18px;
}

.brand-content h1 span {
    color: #e0bf55;
}

.brand-content p {
    color: #c4d0df;
    font-size: 14px;
    line-height: 1.9;
    max-width: 370px;
}

.brand-divider {
    width: 65px;
    height: 3px;
    background: #d4af37;
    margin: 25px 0;
    border-radius: 5px;
}

.brand-feature {
    display: flex;
    align-items: center;
    gap: 13px;
    color: #dce5ef;
    font-size: 13px;
    margin-top: 17px;
}

.brand-feature i {
    color: #e0bf55;
    font-size: 16px;
    width: 20px;
}

.brand-footer {
    color: #9fb0c4;
    font-size: 11px;
    letter-spacing: .5px;
}

/* RIGHT PANEL */

.login-form-panel {
    padding: 65px 55px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.form-heading {
    margin-bottom: 35px;
}

.form-heading .welcome {
    color: #b18b25;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2.5px;
}

.form-heading h2 {
    color: #07182f;
    font-size: 31px;
    font-weight: 800;
    margin: 12px 0 9px;
}

.form-heading p {
    color: #7b8798;
    font-size: 13px;
    line-height: 1.7;
}

.form-group {
    margin-bottom: 22px;
}

.form-group label {
    display: block;
    color: #26364a;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 10px;
}

.input-wrapper {
    height: 54px;
    display: flex;
    align-items: center;
    border: 1px solid #dce2eb;
    border-radius: 11px;
    background: #f9fafc;
    padding: 0 15px;
    transition: .25s;
}

.input-wrapper:focus-within {
    border-color: #b18b25;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(212,175,55,.12);
}

.input-wrapper > i {
    color: #a18a50;
    font-size: 15px;
    margin-right: 12px;
}

.input-wrapper input {
    width: 100%;
    height: 100%;
    border: none;
    outline: none;
    background: transparent;
    color: #10233c;
    font-size: 13px;
    font-family: inherit;
}

.input-wrapper input::placeholder {
    color: #aab3c0;
}

.password-toggle {
    border: none;
    background: none;
    color: #8793a3;
    cursor: pointer;
    padding: 7px;
    font-size: 15px;
}

.login-error {
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #b42318;
    padding: 12px 14px;
    border-radius: 9px;
    font-size: 12px;
    line-height: 1.6;
    margin-bottom: 20px;
}

.login-error i {
    margin-right: 7px;
}

.login-button {
    width: 100%;
    height: 54px;
    border: none;
    border-radius: 11px;
    background: linear-gradient(110deg, #07182f, #153b64);
    color: #e5c45d;
    font-family: inherit;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: .5px;
    cursor: pointer;
    margin-top: 8px;
    box-shadow: 0 9px 20px rgba(7,24,47,.17);
    transition: .25s;
}

.login-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 13px 25px rgba(7,24,47,.25);
}

.login-button i {
    margin-right: 9px;
}

.secure-note {
    text-align: center;
    color: #99a3b1;
    font-size: 11px;
    margin-top: 25px;
}

.secure-note i {
    color: #b18b25;
    margin-right: 5px;
}

@media(max-width: 800px) {

    .login-wrapper {
        max-width: 520px;
        grid-template-columns: 1fr;
    }

    .login-brand {
        padding: 35px;
        min-height: 340px;
    }

    .brand-logo {
        width: 85px;
        height: 85px;
        margin-bottom: 22px;
    }

    .brand-content h1 {
        font-size: 27px;
    }

    .brand-feature {
        display: none;
    }

    .login-form-panel {
        padding: 45px 35px;
    }

}

@media(max-width: 450px) {

    body {
        padding: 12px;
    }

    .login-brand {
        padding: 28px 24px;
    }

    .login-form-panel {
        padding: 38px 24px;
    }

    .form-heading h2 {
        font-size: 27px;
    }

}

</style>

</head>

<body>

<div class="login-wrapper">

    <!-- BRAND PANEL -->
    <section class="login-brand">

        <div class="brand-content">

            <div class="brand-logo">

                <img
                    src="<?php echo $base_url; ?>/assets/images/school-logo.jpeg"
                    alt="DAAES School Logo"
                    onerror="this.style.display='none'"
                >

            </div>

            <div class="brand-tag">
                DR. AFTAB ABRO EDUCATION SYSTEM
            </div>

            <h1>
                Welcome to<br>
                <span>DAAES School</span>
            </h1>

            <p>
                A dedicated administrative portal designed
                to manage school operations with excellence,
                security and efficiency.
            </p>

            <div class="brand-divider"></div>

            <div class="brand-feature">
                <i class="fas fa-shield-alt"></i>
                Secure Administrator Access
            </div>

            <div class="brand-feature">
                <i class="fas fa-graduation-cap"></i>
                Excellence Through Education
            </div>

        </div>

        <div class="brand-footer">
            © <?php echo date('Y'); ?> DAAES School. All Rights Reserved.
        </div>

    </section>


    <!-- LOGIN FORM -->
    <section class="login-form-panel">

        <div class="form-heading">

            <span class="welcome">ADMINISTRATOR PORTAL</span>

            <h2>Sign In</h2>

            <p>
                Enter your administrator credentials to
                access the school management dashboard.
            </p>

        </div>

        <?php if ($error): ?>

            <div class="login-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>

        <?php endif; ?>

        <form action="login_process.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8'); ?>"
            >

            <div class="form-group">

                <label for="username">USERNAME OR EMAIL</label>

                <div class="input-wrapper">

                    <i class="fas fa-user-shield"></i>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username or email"
                        maxlength="150"
                        autocomplete="username"
                        required
                    >

                </div>

            </div>

            <div class="form-group">

                <label for="password">PASSWORD</label>

                <div class="input-wrapper">

                    <i class="fas fa-lock"></i>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword()"
                        aria-label="Show or hide password"
                    >
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>

                </div>

            </div>

            <button type="submit" class="login-button">

                <i class="fas fa-sign-in-alt"></i>
                SIGN IN TO DASHBOARD

            </button>

        </form>

        <div class="secure-note">
            <i class="fas fa-shield-alt"></i>
            Authorized administrators only
        </div>

    </section>

</div>

<script>

function togglePassword() {

    const password = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');

    if (password.type === 'password') {

        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');

    } else {

        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');

    }

}

</script>

</body>
</html>
