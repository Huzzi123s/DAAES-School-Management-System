
<?php
$current_page = 'admissions';
include 'includes/header.php';
?>

<main class="admissions-page">

    <!-- HERO SECTION -->
    <section class="admissions-hero">

        <div class="admissions-hero-content">

            <span class="admissions-tag">
                DAAES SCHOOL
            </span>

            <h1>
                Begin Your Child's
                <span>Bright Future</span>
            </h1>

            <p>
                Give your child the opportunity to learn,
                grow and achieve excellence in a supportive
                and inspiring educational environment.
            </p>

            <a href="#admission-process" class="admissions-btn">
                Explore Admission Process
            </a>

        </div>

    </section>


    <!-- INTRODUCTION -->
    <section class="admissions-intro">

        <div class="admissions-container">

            <div class="admissions-heading">
                <span>WELCOME TO DAAES SCHOOL</span>
                <h2>Admissions at DAAES School</h2>
            </div>

            <p>
                At DAAES School, we welcome students into an
                environment where learning, discipline and
                personal development go hand in hand.

                We believe every child deserves quality education
                and the opportunity to build a successful future.
            </p>

        </div>

    </section>


    <!-- WHY CHOOSE US -->
    <section class="admissions-benefits">

        <div class="admissions-container">

            <div class="admissions-heading">
                <span>WHY CHOOSE US</span>
                <h2>A Place to Learn and Grow</h2>

                <p>
                    Providing students with opportunities
                    to develop academically and personally.
                </p>
            </div>

            <div class="benefits-grid">

                <div class="benefit-card">
                    <div class="benefit-icon">📚</div>
                    <h3>Quality Education</h3>
                    <p>
                        Building strong academic foundations
                        through effective teaching.
                    </p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">👩‍🏫</div>
                    <h3>Dedicated Teachers</h3>
                    <p>
                        Supporting students through guidance,
                        care and individual attention.
                    </p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">🌟</div>
                    <h3>Character Building</h3>
                    <p>
                        Encouraging discipline, confidence,
                        respect and responsibility.
                    </p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">💻</div>
                    <h3>Learning Environment</h3>
                    <p>
                        Promoting creativity, knowledge
                        and modern learning skills.
                    </p>
                </div>

            </div>

        </div>

    </section>


    <!-- ADMISSION PROCESS -->
    <section class="admission-process" id="admission-process">

        <div class="admissions-container">

            <div class="admissions-heading">
                <span>HOW TO APPLY</span>
                <h2>Our Admission Process</h2>

                <p>
                    Admissions are conducted through
                    an in-person visit to our school.
                </p>
            </div>

            <div class="process-grid">

                <div class="process-card">
                    <span class="process-number">01</span>
                    <div class="process-icon">🏫</div>
                    <h3>Visit Our School</h3>
                    <p>
                        Parents or guardians visit
                        the school campus to discuss admission.
                    </p>
                </div>

                <div class="process-card">
                    <span class="process-number">02</span>
                    <div class="process-icon">📄</div>
                    <h3>Collect Admission Form</h3>
                    <p>
                        Obtain the admission form
                        directly from the school office.
                    </p>
                </div>

                <div class="process-card">
                    <span class="process-number">03</span>
                    <div class="process-icon">📁</div>
                    <h3>Submit Documents</h3>
                    <p>
                        Complete the form and submit
                        the required documents at school.
                    </p>
                </div>

                <div class="process-card">
                    <span class="process-number">04</span>
                    <div class="process-icon">🎓</div>
                    <h3>Admission Confirmation</h3>
                    <p>
                        The school office will guide
                        parents regarding the admission decision.
                    </p>
                </div>

            </div>

        </div>

    </section>


    <!-- CLASSES AVAILABLE -->
    <section class="admissions-classes">

        <div class="admissions-container">

            <div class="admissions-heading">
                <span>JOIN OUR SCHOOL</span>
                <h2>Classes Available</h2>

                <p>
                    We welcome students for different
                    stages of their educational journey.
                </p>
            </div>

            <div class="admission-classes-grid">

                <?php

                $classes = [
                    "Playgroup",
                    "Nursery",
                    "Class 1",
                    "Class 2",
                    "Class 3",
                    "Class 4",
                    "Class 5",
                    "Class 6",
                    "Class 7",
                    "Class 8",
                    "Class 9",
                    "Class 10 (Matric)"
                ];

                foreach ($classes as $class) {
                ?>

                    <div class="admission-class-box">
                        <span>✦</span>
                        <?php echo htmlspecialchars($class); ?>
                    </div>

                <?php } ?>

            </div>

        </div>

    </section>


    <!-- REQUIRED DOCUMENTS -->
    <section class="admission-documents">

        <div class="admissions-container">

            <div class="admissions-heading">
                <span>BE PREPARED</span>
                <h2>Required Documents</h2>

                <p>
                    Parents are requested to bring the
                    following documents when visiting the school.
                </p>
            </div>

            <div class="documents-content">

                <div class="documents-icon">
                    📂
                </div>

                <div class="documents-list">

                    <div class="document-item">
                        <span>✓</span>
                        Child's Birth Certificate
                    </div>

                    <div class="document-item">
                        <span>✓</span>
                        Child's B-Form
                    </div>

                    <div class="document-item">
                        <span>✓</span>
                        Recent Passport-Size Photographs
                    </div>

                    <div class="document-item">
                        <span>✓</span>
                        Father's CNIC Copy
                    </div>

                    <div class="document-item">
                        <span>✓</span>
                        Mother's CNIC Copy
                    </div>

                </div>

            </div>

            <p class="documents-note">
                Please confirm the required documents
                with the school office before your visit.
            </p>

        </div>

    </section>


    <!-- IMPORTANT NOTICE -->
    <section class="admission-notice">

        <div class="admissions-container">

            <div class="notice-content">

                <div class="notice-icon">ℹ️</div>

                <div>
                    <h3>Important Admission Information</h3>

                    <p>
                        All admission procedures must be completed
                        physically at the school campus.
                        Online admission applications and registrations
                        are not available.
                        Parents or guardians must visit the school
                        office for admission forms and further details.
                    </p>
                </div>

            </div>

        </div>

    </section>


    <!-- FINAL CTA -->
    <section class="admissions-cta">

        <div class="admissions-container">

            <span>YOUR CHILD'S FUTURE STARTS HERE</span>

            <h2>
                We Look Forward to Welcoming You!
            </h2>

            <p>
                Visit DAAES School and take the first step
                towards your child's bright future.
            </p>


        </div>

    </section>

</main>

<?php include 'includes/footer.php'; ?>
