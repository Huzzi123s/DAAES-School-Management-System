
<?php
$current_page = 'gallery';
include 'includes/header.php';

/*
    Gallery images folder:
    assets/images/gallery/
*/

$gallery_items = [

    [
        'image' => 'assets/images/gallery/event1.jpg',
        'title' => 'School Events',
        'category' => 'events',
        'description' => 'Special moments and school events.'
    ],

    [
        'image' => 'assets/images/gallery/event2.jpg',
        'title' => 'School Celebrations',
        'category' => 'celebrations',
        'description' => 'Celebrating special occasions together.'
    ],

    [
        'image' => 'assets/images/gallery/class1.jpg',
        'title' => 'Classroom Activities',
        'category' => 'classroom',
        'description' => 'Learning through engaging activities.'
    ],

    [
        'image' => 'assets/images/gallery/sports1.jpg',
        'title' => 'Sports Activities',
        'category' => 'sports',
        'description' => 'Encouraging teamwork and participation.'
    ],

    [
        'image' => 'assets/images/gallery/campus1.jpg',
        'title' => 'School Campus',
        'category' => 'campus',
        'description' => 'A glimpse of our school environment.'
    ],

    [
        'image' => 'assets/images/gallery/class2.jpg',
        'title' => 'Learning Activities',
        'category' => 'classroom',
        'description' => 'Creating meaningful learning experiences.'
    ],

    [
        'image' => 'assets/images/gallery/event3.jpg',
        'title' => 'School Functions',
        'category' => 'events',
        'description' => 'Memorable school gatherings.'
    ],

    [
        'image' => 'assets/images/gallery/sports2.jpg',
        'title' => 'Games & Sports',
        'category' => 'sports',
        'description' => 'Promoting healthy and active participation.'
    ],

    [
        'image' => 'assets/images/gallery/celebration1.jpg',
        'title' => 'Special Celebrations',
        'category' => 'celebrations',
        'description' => 'Sharing happiness and special moments.'
    ],

    [
        'image' => 'assets/images/gallery/campus2.jpg',
        'title' => 'Our Learning Space',
        'category' => 'campus',
        'description' => 'An environment for learning and growth.'
    ],

    [
        'image' => 'assets/images/gallery/class3.jpg',
        'title' => 'Creative Learning',
        'category' => 'classroom',
        'description' => 'Encouraging creativity and curiosity.'
    ],

    [
        'image' => 'assets/images/gallery/event4.jpg',
        'title' => 'School Memories',
        'category' => 'events',
        'description' => 'Capturing special moments at DAAES School.'
    ]

];
?>

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


            <!-- FILTER BUTTONS -->
            <div class="gallery-filters">

                <button class="gallery-filter active"
                        data-filter="all">
                    All Photos
                </button>

                <button class="gallery-filter"
                        data-filter="events">
                    School Events
                </button>

                <button class="gallery-filter"
                        data-filter="classroom">
                    Classroom
                </button>

                <button class="gallery-filter"
                        data-filter="celebrations">
                    Celebrations
                </button>

                <button class="gallery-filter"
                        data-filter="sports">
                    Sports
                </button>

                <button class="gallery-filter"
                        data-filter="campus">
                    Campus
                </button>

            </div>


            <!-- GALLERY GRID -->
            <div class="gallery-grid">

                <?php foreach ($gallery_items as $item) { ?>

                    <div class="gallery-item"
                         data-category="<?php echo htmlspecialchars($item['category']); ?>">

                        <div class="gallery-image">

                            <img
                                src="<?php echo htmlspecialchars($item['image']); ?>"
                                alt="<?php echo htmlspecialchars($item['title']); ?>"
                                loading="lazy"
                            >

                            <div class="gallery-overlay">

                                <span class="gallery-image-icon">
                                    ↗
                                </span>

                                <h3>
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </h3>

                                <p>
                                    <?php echo htmlspecialchars($item['description']); ?>
                                </p>

                            </div>

                            <button
                                type="button"
                                class="gallery-view-btn"
                                aria-label="View image"
                                data-image="<?php echo htmlspecialchars($item['image']); ?>"
                                data-title="<?php echo htmlspecialchars($item['title']); ?>"
                            >
                                +
                            </button>

                        </div>

                    </div>

                <?php } ?>

            </div>

            <p class="gallery-empty" hidden>
                No images available in this category yet.
            </p>

        </div>

    </section>


    <!-- IMAGE LIGHTBOX -->
    <div class="gallery-lightbox" id="galleryLightbox">

        <button
            type="button"
            class="lightbox-close"
            id="lightboxClose"
            aria-label="Close image preview">
            &times;
        </button>

        <div class="lightbox-content">

            <img src="" alt="Gallery Preview" id="lightboxImage">

            <h3 id="lightboxTitle"></h3>

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

    const filters = document.querySelectorAll('.gallery-filter');
    const items = document.querySelectorAll('.gallery-item');
    const emptyMessage = document.querySelector('.gallery-empty');

    const lightbox = document.getElementById('galleryLightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxTitle = document.getElementById('lightboxTitle');
    const closeButton = document.getElementById('lightboxClose');

    // CATEGORY FILTER
    filters.forEach(function (button) {

        button.addEventListener('click', function () {

            filters.forEach(function (filter) {
                filter.classList.remove('active');
            });

            this.classList.add('active');

            const selectedCategory = this.dataset.filter;
            let visibleCount = 0;

            items.forEach(function (item) {

                const category = item.dataset.category;

                if (
                    selectedCategory === 'all' ||
                    category === selectedCategory
                ) {

                    item.classList.remove('gallery-hidden');
                    visibleCount++;

                } else {

                    item.classList.add('gallery-hidden');

                }

            });

            emptyMessage.hidden = visibleCount !== 0;

        });

    });


    // OPEN IMAGE PREVIEW
    document.querySelectorAll('.gallery-view-btn').forEach(function (button) {

        button.addEventListener('click', function () {

            lightboxImage.src = this.dataset.image;
            lightboxTitle.textContent = this.dataset.title;

            lightbox.classList.add('show');

            document.body.style.overflow = 'hidden';

        });

    });


    // CLOSE IMAGE PREVIEW
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
