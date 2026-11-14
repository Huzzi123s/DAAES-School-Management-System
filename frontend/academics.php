
<?php
$current_page = 'academics';
include 'includes/header.php';
?>

<main class="academics-page">

    <!-- HERO SECTION -->
    <section class="academics-hero">
        <div class="academics-hero-content">

            <span class="academics-tag">
                DAAES SCHOOL
            </span>

            <h1>
                Academics & <span>Learning</span>
            </h1>

            <p>
                Building strong foundations, developing creative minds
                and preparing students for a brighter future.
            </p>

            <a href="#academic-programs" class="academics-btn">
                Explore Academics
            </a>

        </div>
    </section>


    <!-- INTRODUCTION -->
    <section class="academics-intro">
        <div class="academics-container">

            <div class="academics-heading">
                <span>OUR ACADEMIC APPROACH</span>
                <h2>Excellence in Education</h2>
            </div>

            <p>
                At DAAES School, we believe that education is the
                foundation of a successful future. Our academic
                approach focuses on quality education, creativity,
                discipline and the overall development of students.
            </p>

        </div>
    </section>


    <!-- ACADEMIC PROGRAMS -->
    <section class="academic-programs" id="academic-programs">
        <div class="academics-container">

            <div class="academics-heading">
                <span>WHAT WE OFFER</span>
                <h2>Our Academic Programs</h2>
                <p>
                    A structured learning journey for every student.
                </p>
            </div>

            <div class="academic-cards">

                <div class="academic-card">
                    <div class="academic-icon">📚</div>
                    <h3>Primary Education</h3>
                    <p>
                        Developing basic knowledge, reading,
                        writing and essential learning skills.
                    </p>
                </div>

                <div class="academic-card">
                    <div class="academic-icon">🎓</div>
                    <h3>Middle Education</h3>
                    <p>
                        Strengthening academic concepts,
                        independent thinking and problem-solving.
                    </p>
                </div>

                <div class="academic-card">
                    <div class="academic-icon">🏆</div>
                    <h3>Secondary Education</h3>
                    <p>
                        Preparing students for matriculation
                        examinations and future academic goals.
                    </p>
                </div>

            </div>
        </div>
    </section>


    <!-- CLASSES -->
    <section class="academic-classes">
        <div class="academics-container">

            <div class="academics-heading">
                <span>OUR CLASSES</span>
                <h2>Classes We Offer</h2>
            </div>

            <div class="classes-grid">

                <?php

                $classes = [
                    "Playgroup",
                    "Library",
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

                    <div class="class-box">

                        <span>✦</span>

                        <?php echo htmlspecialchars($class); ?>

                    </div>

                <?php } ?>

            </div>

        </div>
    </section>


    <!-- SUBJECTS -->
    <section class="academic-subjects">
        <div class="academics-container">

            <div class="academics-heading">
                <span>OUR CURRICULUM</span>
                <h2>Subjects We Teach</h2>

                <p>
                    Helping students develop knowledge across
                    different areas of learning.
                </p>
            </div>

            <div class="subjects-grid">

                <div class="subject-box">📖 English</div>
                <div class="subject-box">📝 Urdu</div>
                <div class="subject-box">☪ Islamic Studies</div>
                <div class="subject-box">🔢 Mathematics</div>
                <div class="subject-box">💻 Computer Studies</div>
                <div class="subject-box">⚡ Physics</div>
                <div class="subject-box">🧪 Chemistry</div>
                <div class="subject-box">🌿 Sindhi</div>
                <div class="subject-box">🌍 General Knowledge</div>
                <div class="subject-box">🇵🇰 Pakistan Studies</div>
                <div class="subject-box">🗺 Social Studies</div>
                <div class="subject-box">🔬 General Science</div>

            </div>
        </div>
    </section>


    <!-- TEACHING METHODOLOGY -->
    <section class="teaching-method">
        <div class="academics-container">

            <div class="academics-heading">
                <span>HOW WE TEACH</span>
                <h2>Our Teaching Methodology</h2>
            </div>

            <div class="teaching-grid">

                <div class="teaching-item">
                    <h3>01. Interactive Learning</h3>
                    <p>
                        Encouraging students to participate
                        actively in classroom activities.
                    </p>
                </div>

                <div class="teaching-item">
                    <h3>02. Practical Education</h3>
                    <p>
                        Connecting classroom concepts with
                        real-life examples and activities.
                    </p>
                </div>

                <div class="teaching-item">
                    <h3>03. Individual Attention</h3>
                    <p>
                        Supporting students according to
                        their learning needs and abilities.
                    </p>
                </div>

                <div class="teaching-item">
                    <h3>04. Character Building</h3>
                    <p>
                        Developing discipline, confidence,
                        respect and responsibility.
                    </p>
                </div>

            </div>
        </div>
    </section>


    <!-- EXAMINATION -->
    <section class="academic-examination">
        <div class="academics-container">

            <div class="academics-heading">
                <span>STUDENT PERFORMANCE</span>
                <h2>Examination & Assessment</h2>
            </div>

            <div class="examination-content">

                <p>
                    We regularly evaluate students' academic
                    progress through different assessment methods.
                    These assessments help teachers understand
                    students' performance and identify areas
                    for improvement.
                </p>

                <ul>
                    <li>Class Tests</li>
                    <li>Monthly Assessments</li>
                    <li>Mid-Term Examinations</li>
                    <li>Final Examinations</li>
                    <li>Homework and Class Participation</li>
                </ul>

            </div>
        </div>
    </section>


    <!-- CTA SECTION -->
    <section class="academics-cta">
        <div class="academics-container">

            <h2>Education Today, Success Tomorrow!</h2>

            <p>
                Give your child the opportunity to learn,
                grow and achieve their dreams.
            </p>

            <a href="admissions.php" class="academics-btn">
                Apply for Admission
            </a>

        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>
