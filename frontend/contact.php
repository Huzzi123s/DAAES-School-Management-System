
<?php
$current_page = 'contact';
include 'includes/header.php';

$school_address = "Karachi, Crossing Allah Wala Town, Sector 31/A, Near Jamia Rehmat Masjid";
$school_phone = "0336-3875074";
$school_email = "mhuzaifashiekh@gmail.com";
?>

<main class="contact-page">

    <!-- HERO SECTION -->
    <section class="contact-hero">

        <div class="contact-hero-content">

            <span class="contact-tag">
                DAAES SCHOOL
            </span>

            <h1>
                Get in <span>Touch With Us</span>
            </h1>

            <p>
                We are always happy to assist parents and
                visitors. Contact us or visit our school
                campus for further information.
            </p>

            <a href="#contact-information" class="contact-btn">
                Contact Information
            </a>

        </div>

    </section>


    <!-- INTRODUCTION -->
    <section class="contact-intro">

        <div class="contact-container">

            <div class="contact-heading">

                <span>WE ARE HERE TO HELP</span>

                <h2>Let's Stay Connected</h2>

                <p>
                    Whether you need information about admissions,
                    academics or school activities, our school office
                    is available to guide and assist you.
                </p>

            </div>

        </div>

    </section>


    <!-- CONTACT INFORMATION -->
    <section class="contact-information" id="contact-information">

        <div class="contact-container">

            <div class="contact-heading">

                <span>CONTACT DETAILS</span>

                <h2>Reach Our School</h2>

                <p>
                    Find our contact information and
                    connect with DAAES School.
                </p>

            </div>


            <div class="contact-cards">

                <!-- ADDRESS -->
                <div class="contact-card">

                    <div class="contact-icon">
                        📍
                    </div>

                    <h3>Our Address</h3>

                    <p>
                        <?php echo htmlspecialchars($school_address); ?>
                    </p>

                </div>


                <!-- PHONE -->
                <div class="contact-card">

                    <div class="contact-icon">
                        📞
                    </div>

                    <h3>Phone Number</h3>

                    <p>
                        <a href="tel:03363875074">
                            <?php echo htmlspecialchars($school_phone); ?>
                        </a>
                    </p>

                </div>


                <!-- EMAIL -->
                <div class="contact-card">

                    <div class="contact-icon">
                        ✉️
                    </div>

                    <h3>Email Address</h3>

                    <p>
                        <a href="mailto:mhuzaifashiekh@gmail.com">
                            <?php echo htmlspecialchars($school_email); ?>
                        </a>
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- VISIT SCHOOL -->
    <section class="contact-visit">

        <div class="contact-container">

            <div class="visit-content">

                <div class="visit-text">

                    <span>WE WELCOME YOU</span>

                    <h2>Visit Our School Campus</h2>

                    <p>
                        We encourage parents and guardians
                        to visit DAAES School in person.
                        Our school staff will be happy to
                        provide information about admissions,
                        classes and educational facilities.
                    </p>

                    <a href="admissions.php" class="contact-btn">
                        Admission Information
                    </a>

                </div>

                <div class="visit-icon">
                    🏫
                </div>

            </div>

        </div>

    </section>


    <!-- IMPORTANT INFORMATION -->
    <section class="contact-notice">

        <div class="contact-container">

            <div class="contact-notice-content">

                <div class="contact-notice-icon">
                    ℹ️
                </div>

                <div>

                    <h3>Need Admission Information?</h3>

                    <p>
                        All admission procedures are completed
                        physically at the school campus.
                        Parents or guardians are requested
                        to visit the school office for admission
                        forms and further guidance.
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- FINAL CTA -->
    <section class="contact-cta">

        <div class="contact-container">

            <span>DAAES SCHOOL</span>

            <h2>
                We Look Forward to Meeting You!
            </h2>

            <p>
                Come visit us and become a part
                of our learning community.
            </p>

            <a href="academics.php" class="contact-btn">
                Explore Academics
            </a>

        </div>

    </section>

</main>

<?php include 'includes/footer.php'; ?>
