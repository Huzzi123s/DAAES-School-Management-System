```php
<?php
$current_page = $current_page ?? 'home';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>DAAES School | Quality Education</title>

    <link rel="stylesheet" href="/DAAES_School_Management/assets/css/style.css">
    <link rel="stylesheet" href="/DAAES_School_Management/frontend/assets/css/frontend.css">

    <!-- NAVBAR CUSTOM DESIGN -->
    <style>
        .site-header {
            background: #04152f !important;
            border-bottom: 4px solid #e8ad16;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 18px rgba(0,0,0,0.18);
        }

        .site-header .nav-container {
            max-width: 1450px;
            min-height: 85px;
            margin: auto;
            padding: 0 25px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .school-brand {
            display: none !important;
        }

        .site-header .main-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 48px;
            width: 100%;
        }

        .site-header .main-nav a {
            color: #e8ad16 !important;
            font-size: 20px;
            font-weight: 700;
            padding: 12px 0;
            position: relative;
            transition: 0.3s ease;
            white-space: nowrap;
        }

        .site-header .main-nav a:hover {
            color: #ffffff !important;
        }

        .site-header .main-nav a.active {
            color: #ffffff !important;
        }

        .site-header .main-nav a.active::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 2px;
            width: 100%;
            height: 3px;
            background: #e8ad16;
            border-radius: 5px;
        }

        .site-header .menu-toggle {
            display: none;
            background: #e8ad16;
            color: #04152f;
            border: none;
            border-radius: 7px;
            font-size: 25px;
            padding: 7px 13px;
            cursor: pointer;
        }

        @media (max-width: 1000px) {
            .site-header .main-nav {
                gap: 25px;
            }

            .site-header .main-nav a {
                font-size: 15px;
            }
        }

        @media (max-width: 700px) {
            .site-header .nav-container {
                min-height: 70px;
                padding: 10px 18px;
                justify-content: flex-end;
                flex-wrap: wrap;
            }

            .site-header .menu-toggle {
                display: block;
            }

            .site-header .main-nav {
                display: none;
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: 0;
                background: #061c3c;
                padding: 10px 15px;
                margin-top: 10px;
                border-top: 2px solid #e8ad16;
            }

            .site-header .main-nav.show {
                display: flex;
            }

            .site-header .main-nav a {
                color: #e8ad16 !important;
                font-size: 16px;
                padding: 15px 10px;
                border-bottom: 1px solid rgba(232,173,22,0.2);
            }

            .site-header .main-nav a:hover,
            .site-header .main-nav a.active {
                color: white !important;
            }

            .site-header .main-nav a.active::after {
                display: none;
            }
        }
    </style>
</head>

<body>

<header class="site-header">

    <div class="nav-container">

        <button
            class="menu-toggle"
            id="menuToggle"
            aria-label="Toggle navigation"
            aria-expanded="false"
            type="button">
            ☰
        </button>

        <nav class="main-nav" id="mainNav">

            <a href="index.php"
               class="<?= $current_page == 'home' ? 'active' : '' ?>">
                Home
            </a>

            <a href="about.php"
               class="<?= $current_page == 'about' ? 'active' : '' ?>">
                About Us
            </a>

            <a href="academics.php"
               class="<?= $current_page == 'academics' ? 'active' : '' ?>">
                Academics
            </a>

            <a href="admissions.php"
               class="<?= $current_page == 'admissions' ? 'active' : '' ?>">
                Admissions
            </a>

            <a href="gallery.php"
               class="<?= $current_page == 'gallery' ? 'active' : '' ?>">
                Gallery
            </a>

            <a href="contact.php"
               class="<?= $current_page == 'contact' ? 'active' : '' ?>">
                Contact
            </a>

        </nav>

    </div>

</header>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');

    if (menuToggle && mainNav) {

        menuToggle.addEventListener('click', function () {

            mainNav.classList.toggle('show');

            const isOpen = mainNav.classList.contains('show');

            menuToggle.setAttribute('aria-expanded', isOpen);

            menuToggle.innerHTML = isOpen ? '&times;' : '☰';

        });

    }

});
</script>

<!-- DAAES PYTHON CHATBOT -->
<script src="/DAAES_School_Management/python_chatbot/static/js/chatbot.js" defer></script>

</body>
</html>
```