
<?php
$current_page = 'gallery';
include 'includes/header.php';

$gallery_items = [

    [
        'image' => '../assets/images/1.jfif',
        'title' => 'School Event 1'
    ],

    [
        'image' => '../assets/images/2.jfif',
        'title' => 'School Event 2'
    ],

    [
        'image' => '../assets/images/3.jfif',
        'title' => 'Classroom 1'
    ],

    [
        'image' => '../assets/images/4.jfif',
        'title' => 'Sports 1'
    ],

    [
        'image' => '../assets/images/5.jfif',
        'title' => 'Campus 1'
    ],

    [
        'image' => '../assets/images/6.jfif',
        'title' => 'Classroom 2'
    ]

];
?>

<style>

/* =====================================
   GALLERY PAGE
===================================== */

.gallery-page {
    width: 100%;
    overflow: hidden;
}

.gallery-container {
    width: 92%;
    max-width: 1450px;
    margin: auto;
}

/* =====================================
   HERO SECTION
===================================== */

.gallery-hero {
    min-height: 420px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 70px 20px;

    background: linear-gradient(
        135deg,
        #04152f,
        #0b2b56,
        #061c3c
    );

    color: white;
}

.gallery-hero-content {
    max-width: 850px;
}

.gallery-tag {
    color: #f5cf65;
    font-weight: 700;
    letter-spacing: 4px;
}

.gallery-hero h1 {
    font-size: 55px;
    margin: 20px 0;
    font-weight: 800;
}

.gallery-hero h1 span {
    color: #e8ad16;
}

.gallery-hero p {
    font-size: 17px;
    line-height: 1.8;
    color: #e0e7f0;
}

.gallery-btn {
    display: inline-block;
    margin-top: 25px;
    padding: 14px 32px;
    background: #e8ad16;
    color: #04152f;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 700;
    transition: 0.3s;
}

.gallery-btn:hover {
    background: #f5cf65;
    transform: translateY(-3px);
}

/* =====================================
   INTRO SECTION
===================================== */

.gallery-intro {
    padding: 65px 0 25px;
}

.gallery-heading {
    text-align: center;
    max-width: 750px;
    margin: 0 auto 40px;
}

.gallery-heading > span {
    color: #c18b0b;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 2px;
}

.gallery-heading h2 {
    font-size: 36px;
    color: #04152f;
    margin: 15px 0;
    font-weight: 800;
}

.gallery-heading p {
    color: #666;
    line-height: 1.8;
    font-size: 16px;
}

/* =====================================
   GALLERY SECTION
===================================== */

.school-gallery {
    padding: 55px 0 90px;
    background: #f7f9fc;
}

/* =====================================
   GALLERY GRID
   3 COLUMNS - 2 ROWS
===================================== */

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 30px;
}

/* =====================================
   GALLERY CARDS
===================================== */

.gallery-item {
    width: 100%;
    min-width: 0;
}

.gallery-image {
    width: 100%;
    height: 350px;
    position: relative;
    overflow: hidden;
    border-radius: 14px;
    background: #e6eaf0;

    box-shadow: 0 6px 22px rgba(4, 21, 47, 0.13);

    transition: 0.3s ease;
}

.gallery-image img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    transition: transform 0.5s ease;
}

/* IMAGE HOVER */

.gallery-image:hover img {
    transform: scale(1.08);
}

.gallery-item:hover .gallery-image {
    box-shadow: 0 12px 30px rgba(4, 21, 47, 0.20);
}

/* REMOVE ALL IMAGE LABELS */

.gallery-overlay {
    display: none !important;
}

/* =====================================
   IMAGE VIEW BUTTON
===================================== */

.gallery-view-btn {
    position: absolute;
    top: 50%;
    left: 50%;

    transform: translate(-50%, -50%);

    width: 50px;
    height: 50px;

    border: none;
    border-radius: 50%;

    background: #e8ad16;
    color: #04152f;

    font-size: 28px;
    font-weight: 700;

    cursor: pointer;

    opacity: 0;
    transition: 0.3s;
}

.gallery-image:hover .gallery-view-btn {
    opacity: 1;
}

/* =====================================
   LIGHTBOX
===================================== */

.gallery-lightbox {
    position: fixed;
    inset: 0;

    width: 100%;
    height: 100%;

    background: rgba(0, 0, 0, 0.94);

    z-index: 99999;

    display: none;
    align-items: center;
    justify-content: center;

    padding: 30px;
}

.gallery-lightbox.show {
    display: flex;
}

.lightbox-content {
    max-width: 1100px;
    width: 100%;
    text-align: center;
}

.lightbox-content img {
    max-width: 100%;
    max-height: 82vh;
    object-fit: contain;
    border-radius: 8px;
}

.lightbox-content h3 {
    display: none;
}

.lightbox-close {
    position: absolute;
    top: 20px;
    right: 35px;

    background: transparent;
    border: none;

    color: white;
    font-size: 45px;

    cursor: pointer;
}

/* =====================================
   CTA SECTION
===================================== */

.gallery-cta {
    background: linear-gradient(
        135deg,
        #04152f,
        #0b2b56
    );

    text-align: center;
    padding: 75px 20px;
    color: white;
}

.gallery-cta > .gallery-container > span {
    color: #f5cf65;
    font-weight: 700;
    letter-spacing: 3px;
}

.gallery-cta h2 {
    font-size: 35px;
    margin: 20px 0;
}

.gallery-cta p {
    color: #dce4f0;
    font-size: 17px;
}

/* =====================================
   RESPONSIVE DESIGN
===================================== */

/* TABLETS */

@media (max-width: 992px) {

    .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 22px;
    }

    .gallery-image {
        height: 300px;
    }

}

/* MOBILE */

@media (max-width: 600px) {

    .gallery-container {
        width: 94%;
    }

    .gallery-grid {
        grid-template-columns: repeat(1, 1fr);
        gap: 20px;
    }

    .gallery-image {
        height: 280px;
    }

    .gallery-hero h1 {
        font-size: 36px;
    }

    .gallery-heading h2 {
        font-size: 28px;
    }

    .gallery-cta h2 {
        font-size: 27px;
    }

    .gallery-view-btn {
        opacity: 1;
        width: 42px;
        height: 42px;
        font-size: 24px;
    }

}

</style>


<main class="gallery-page">

    <!-- HERO SECTION -->

    <section class="gallery-hero">

        <div class="gallery-hero-content">

            <span class="gallery-tag">
                DAAES SCHOOL
            </span>

            <h1>
                Our School <span>Gallery</span>
            </h1>

            <p>
                Explore the special moments, exciting activities,
                celebrations and unforgettable experiences
                that make life at DAAES School special.
            </p>

            <a href="#school-gallery" class="gallery-btn">
                Explore Gallery
            </a>

        </div>

    </section>


    <!-- INTRODUCTION -->

    <section class="gallery-intro">

        <div class="gallery-container">

            <div class="gallery-heading">

                <span>CAPTURING SPECIAL MOMENTS</span>

                <h2>Life at DAAES School</h2>

                <p>
                    Every picture tells a story. Take a look at
                    our school activities, learning experiences,
                    celebrations and campus life.
                </p>

            </div>

        </div>

    </section>


    <!-- GALLERY SECTION -->

    <section class="school-gallery" id="school-gallery">

        <div class="gallery-container">

            <div class="gallery-heading">

                <span>EXPLORE OUR MOMENTS</span>

                <h2>Memories in Pictures</h2>

                <p>
                    Discover different aspects of school life
                    through our photo collection.
                </p>

            </div>


            <!-- GALLERY GRID -->

            <div class="gallery-grid">

                <?php foreach ($gallery_items as $item) { ?>

                    <div class="gallery-item">

                        <div class="gallery-image">

                            <img
                                src="<?php echo htmlspecialchars($item['image']); ?>"
                                alt="DAAES School Gallery"
                                loading="lazy"
                            >

                            <button
                                type="button"
                                class="gallery-view-btn"
                                aria-label="View image"
                                data-image="<?php echo htmlspecialchars($item['image']); ?>"
                            >
                                +
                            </button>

                        </div>

                    </div>

                <?php } ?>

            </div>

        </div>

    </section>


    <!-- IMAGE LIGHTBOX -->

    <div class="gallery-lightbox" id="galleryLightbox">

        <button
            type="button"
            class="lightbox-close"
            id="lightboxClose"
            aria-label="Close image preview"
        >
            &times;
        </button>

        <div class="lightbox-content">

            <img
                src=""
                alt="Gallery Preview"
                id="lightboxImage"
            >

        </div>

    </div>


    <!-- CTA SECTION -->

    <section class="gallery-cta">

        <div class="gallery-container">

            <span>DAAES SCHOOL</span>

            <h2>
                Every Moment Is Worth Celebrating!
            </h2>

            <p>
                Learning, growing and creating brighter
                futures together.
            </p>

        </div>

    </section>

</main>


<!-- GALLERY JAVASCRIPT -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const lightbox = document.getElementById('galleryLightbox');

    const lightboxImage = document.getElementById('lightboxImage');

    const closeButton = document.getElementById('lightboxClose');


    // OPEN IMAGE PREVIEW

    document.querySelectorAll('.gallery-view-btn').forEach(function (button) {

        button.addEventListener('click', function () {

            lightboxImage.src = this.dataset.image;

            lightbox.classList.add('show');

            document.body.style.overflow = 'hidden';

        });

    });


    // CLOSE LIGHTBOX

    function closeLightbox() {

        lightbox.classList.remove('show');

        document.body.style.overflow = '';

        lightboxImage.src = '';

    }


    closeButton.addEventListener('click', closeLightbox);


    // CLOSE WHEN CLICKING OUTSIDE IMAGE

    lightbox.addEventListener('click', function (event) {

        if (event.target === lightbox) {
            closeLightbox();
        }

    });


    // CLOSE WITH ESCAPE KEY

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            closeLightbox();
        }

    });

});

</script>

<?php include 'includes/footer.php'; ?>
