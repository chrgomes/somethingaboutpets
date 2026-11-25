<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$user = null;
try {
    $user = current_user();
} catch (Throwable $e) {
}
// Message shown above the contact form after a non-JavaScript submit.
$contactFlash = render_flashes();
layout_start('', ['section' => 'home', 'body_class' => 'home-pages-2']);
?>

    <!-- ========== Hero Style1 Start============= -->

    <div class="hero2" id="main">
        <div class="left-sidebar">
            <div class="swiper-pagination121"></div>
        </div>
        <div class="swiper hero2-slider">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="hero-wrapper">
                        <div class="container">
                            <div class="row align-items-center">
                                <div class="col-lg-6">
                                    <div class="banner-content">
                                        <h1>Your <span>dog</span> is part of our family.</h1>
                                        <p class="sap-hero-sub">Loving pet sitting, dog walking and daily visits in Auckland. Book online in a couple of minutes.</p>
                                        <div class="sap-hero-btns">
                                            <a class="primary-btn2" href="book.php">Book Your Day</a>
                                            <a class="sap-link-btn" href="#services">See services <i class="bi bi-arrow-down"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 d-flex justify-content-center">
                                    <div class="hero-img">
                                        <img class="img-fluid" src="assets/images/bg/hero-dog-781x713.jpg" alt="A happy dog looking at the camera" width="781" height="713" fetchpriority="high">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="hero-wrapper">
                        <div class="container">
                            <div class="row align-items-center">
                                <div class="col-lg-6">
                                    <div class="banner-content">
                                        <h2 class="h1">Your <span>cat</span> is part of our family.</h2>
                                        <p class="sap-hero-sub">Loving pet sitting, dog walking and daily visits in Auckland. Book online in a couple of minutes.</p>
                                        <div class="sap-hero-btns">
                                            <a class="primary-btn2" href="book.php">Book Your Day</a>
                                            <a class="sap-link-btn" href="#services">See services <i class="bi bi-arrow-down"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 d-flex justify-content-center">
                                    <div class="hero-img">
                                        <img class="img-fluid" src="assets/images/bg/hero-cat-781x713.jpg" alt="A relaxed cat" width="781" height="713" loading="lazy">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="hero-wrapper">
                        <div class="container">
                            <div class="row align-items-center">
                                <div class="col-lg-6">
                                    <div class="banner-content">
                                        <h2 class="h1">Your <span>fuzzy pets</span> are part of our family too.</h2>
                                        <p class="sap-hero-sub">Loving pet sitting, dog walking and daily visits in Auckland. Book online in a couple of minutes.</p>
                                        <div class="sap-hero-btns">
                                            <a class="primary-btn2" href="book.php">Book Your Day</a>
                                            <a class="sap-link-btn" href="#services">See services <i class="bi bi-arrow-down"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 d-flex justify-content-center">
                                    <div class="hero-img">
                                        <img class="img-fluid" src="assets/images/bg/hero-fuzzypets-781x713.jpg" alt="A small fuzzy pet" width="781" height="713" loading="lazy">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="right-sidebar">
        </div>
    </div>

    <!-- ========== Hero Style1 end============= -->

    <!-- ========== H2 Services Area Start============= -->

    <section class="h2-services-area" id="services">
      <div class="services-top">
        <div class="services-section-card">
            <div class="card-vector">
                <img class="services-card-vect-01" src="assets/images/bg/h2-services-title-rt.png" alt="">
                <img class="services-card-vect-02" src="assets/images/bg/h2-services-title-lb.png" alt="">
            </div>
            <div class="services-title">
                <h2>See All Our <span>Services.</span></h2>
                <a class="primary-btn2" href="book.php">Book Your Day</a>
            </div>
        </div>
        <div class="swiper h2-services-slider">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="services-card">
                        <div class="card-vector">
                            <img class="services-card-vect-01" src="assets/images/bg/services-card-vec.png" alt="">
                            <img class="services-card-vect-02" src="assets/images/bg/services-card-vec2.png" alt="">
                        </div>
                        <div class="services-icon">
                                <lord-icon
                                    src="https://cdn.lordicon.com/uwdzqtsd.json"
                                    trigger="hover"
                                    colors="primary:#e83a30,secondary:#e83a30"
                                    style="width:100px;height:100px">
                                </lord-icon>
                        </div>
                        <div class="services-content">
                            <h3><a href="book.php?service=1">Dog Walking</a></h3>
                            <p>Elevate your pet's happiness with our dog walking service. Something About Pets offers tailored walks, providing exercise 
                                and care. Trust us to treat your furry friend like family, ensuring their well-being and your peace of mind.</p>
                            <div class="more-btn">
                                <a href="book.php?service=1">Book This Service<img src="assets/images/icon/h2-btn-arrow.svg" alt=""></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="services-card">
                        <div class="card-vector">
                            <img class="services-card-vect-01" src="assets/images/bg/services-card-vec.png" alt="">
                            <img class="services-card-vect-02" src="assets/images/bg/services-card-vec2.png" alt="">
                        </div>
                        <div class="services-icon">
                            <lord-icon
                                src="https://cdn.lordicon.com/dxrihtba.json"
                                trigger="hover"
                                colors="primary:#e83a30,secondary:#e83a30"
                                style="width:100px;height:100px">
                            </lord-icon>
                        </div>
                        <div class="services-content">
                            <h3><a href="book.php?service=2">Daily Visit</a></h3>
                            <p>Ensure your pet's daily joy with Something About Pets. Our daily visits promise personalized attention, playtime, and care. 
                                Count on us to meet your furry friend's needs, providing peace of mind while you're away.</p>
                            <div class="more-btn">
                                <a href="book.php?service=2">Book This Service<img src="assets/images/icon/h2-btn-arrow.svg" alt=""></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="services-card">
                        <div class="card-vector">
                            <img class="services-card-vect-01" src="assets/images/bg/services-card-vec.png" alt="">
                            <img class="services-card-vect-02" src="assets/images/bg/services-card-vec2.png" alt="">
                        </div>
                        <div class="services-icon">
                            <lord-icon
                                src="https://cdn.lordicon.com/lilqkhnm.json"
                                trigger="hover"
                                colors="primary:#e83a30,secondary:#e83a30"
                                style="width:100px;height:100px">
                            </lord-icon>
                        </div>
                        <div class="services-content">
                            <h3><a href="book.php?service=3">Pet Taxi</a></h3>
                            <p>Rely on Something About Pets for hassle-free pet transport. Our pet taxi service ensures safe and comfortable journeys 
                                for your furry companions. Trust us to chauffeur your pets with care, offering convenience for their travels and 
                                your peace of mind.</p>
                            <div class="more-btn">
                                <a href="book.php?service=3">Book This Service<img src="assets/images/icon/h2-btn-arrow.svg" alt=""></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="services-card">
                        <div class="card-vector">
                            <img class="services-card-vect-01" src="assets/images/bg/services-card-vec.png" alt="">
                            <img class="services-card-vect-02" src="assets/images/bg/services-card-vec2.png" alt="">
                        </div>
                        <div class="services-icon">
                            <lord-icon
                                src="https://cdn.lordicon.com/odgzzleh.json"
                                trigger="hover"
                                colors="primary:#e83a30,secondary:#e83a30"
                                style="width:100px;height:100px">
                            </lord-icon>
                        </div>
                        <div class="services-content">
                            <h3><a href="book.php?service=4">In-house Sitting</a></h3>
                            <p>Choose Something About Pets for worry-free in-house sitting. Our dedicated team ensures your pets receive personalized 
                                care in the comfort of their own home. Trust us to maintain routines, offer companionship, and provide peace of 
                                mind while you're away.</p>
                            <div class="more-btn">
                                <a href="book.php?service=4">Book This Service<img src="assets/images/icon/h2-btn-arrow.svg" alt=""></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>
      <div class="services-btm pt-120 mb-120" id="about">
        <div class="container">
            <div class="row">
                <div class="col-lg-5">
                    <div class="services-img">
                        <img class="img-fluid" src="assets/images/bg/h2-services-principal-510x561.jpg" alt="Jessica Conners with a dog in a yellow raincoat" loading="lazy" width="510" height="561">
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="services-content">
                            <h2>Compassionate, experienced animal lover for all your pet-sitting needs.</h2>
                            <p>Something About Pets dedicates itself to fulfilling your pet-sitting needs with a genuine passion for animals. Our commitment involves building lasting connections with your pets
                                and delivering top-notch, personalized care, including dog walking, pet sitting, daily visits and in-house sitting. Rest assured, we treat your fur babies as our own, ensuring
                                they experience the highest quality care during your absence.
                            </p>
                            <div class="author-area">
                                <div class="author-quat">
                                    <p><sup><img src="assets/images/icon/author-quat-icon.svg" alt="" aria-hidden="true"></sup>
                                        Our pets grace our lives as Earth's angels, their unwavering loyalty and boundless love is a testament to the importance of dedicated care and service - a cherished
                                        responsibility for the greatest gift we've received.
                                    </p>
                                </div>
                                <div class="author-name-deg">
                                    <h4>Jessica Conners</h4>
                                    <span>Principal, Something About Pets</span>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </section>

    <!-- ========== H2 Services Area End============= -->

    <!-- ========== H2 Choose Area Start============= -->

    <div class="h2-choose-area mb-120">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title2 text-center">
                        <h2>Why Choose Us</h2>
                        <p>At Something About Pets, we believe that caring for your pets is an honor and a commitment to their well-being. Dedicated to attending to 
                            every need, showering your furry friends with love, and ensuring a joyous and healthy life for your cherished companions. Every pet 
                            deserves the best, and we're here to provide just that.
                        </p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center pt-90 pb-90 g-4">
                <div class="col-lg-3 col-md-4 col-sm-6 col-10">
                    <div class="single-card">
                        <div class="icon ctr">
                            <lord-icon
                                src="https://cdn.lordicon.com/yntbapme.json"
                                trigger="hover"
                                colors="primary:#e83a30,secondary:#e83a30"
                                style="width:100px;height:100px">
                            </lord-icon>
                        </div>
                        <div class="content">
                            <h4>Personalized Care</h4>
                            <p>
                                Experience personalized pet care at its finest with Something About Pets. Our dedicated team tailors every service to your furry 
                                friend's unique needs, ensuring they receive the individual attention, love, and care they deserve. Trust us to go above and 
                                beyond for your pet's well-being and happiness.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6 col-10">
                    <div class="single-card">
                        <div class="icon ctr">
                            <lord-icon
                                src="https://cdn.lordicon.com/edtgitad.json"
                                trigger="hover"
                                colors="primary:#e83a30,secondary:#e83a30"
                                style="width:100px;height:100px">
                            </lord-icon>
                        </div>
                        <div class="content">
                            <h4>Trusted and Dedicated Team</h4>
                            <p>At Something About Pets, trust our dedicated team for unparalleled pet care. With a genuine love for animals, we prioritize your 
                                pets' well-being. Our trustworthy professionals provide top-tier services, ensuring your furry companions receive the highest 
                                level of care and attention.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6 col-10">
                    <div class="single-card">
                        <div class="icon ctr">
                            <lord-icon
                                src="https://cdn.lordicon.com/zumnpnry.json"
                                trigger="hover"
                                colors="primary:#e83a30,secondary:#e83a30"
                                style="width:100px;height:100px">
                            </lord-icon>
                        </div>
                        <div class="content">
                            <h4>Peace of Mind</h4>
                            <p>Experience true peace of mind with Something About Pets. Our dedicated team ensures your furry companions receive exceptional care, 
                                providing a worry-free experience for you. Trust us for top-tier pet services, allowing you to relax knowing your pets are in 
                                capable and caring hands.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6 col-10">
                    <div class="single-card">
                        <div class="icon ctr">
                            <lord-icon
                                src="https://cdn.lordicon.com/qeltvbrs.json"
                                trigger="hover"
                                colors="primary:#e83a30,secondary:#e83a30"
                                style="width:100px;height:100px">
                            </lord-icon>
                        </div>
                        <div class="content">
                            <h4>Comforting Environment</h4>
                            <p>Create a comforting haven for your pets with Something About Pets. Our dedicated team ensures a nurturing environment, providing 
                                love and care for your furry friends. Trust us to prioritize their well-being, offering a soothing and comforting 
                                atmosphere in your absence.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== H2 Choose Area End============= -->

    <div class="paw-trail mb-60" aria-hidden="true"></div>

    <!-- ========== H2 Warking Process Area Start============= -->

    <section class="h2-working-process mb-120" id="how-it-works">
        <div class="container">
            <div class="row mb-60">
                <div class="col-lg-12">
                    <div class="section-title2 text-center">
                        <h2>Three Steps and Enjoy Your Day.</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="work-process-area">
                        <div class="work-process-card">
                            <div class="sl-no">
                                <span>Step</span>
                                <h3>1</h3>
                            </div>
                            <div class="icon">
                                <lord-icon
                                    src="https://cdn.lordicon.com/jtkfemwz.json"
                                    trigger="hover"
                                    colors="primary:#e83a30,secondary:#e83a30"
                                    style="width:150px;height:150px">
                                </lord-icon>
                            </div>
                            <div class="work-content text-center">
                                <h3>Select A Service</h3>
                                <p>Choose Something About Pets for exceptional pet care and service. Our dedicated team offers tailored services, 
                                    prioritizing your pet's happiness and well-being with love and expertise.</p>
                            </div>
                        </div>

                        <div class="work-proces-arrow">
                            <img class="img-fluid" src="assets/images/bg/h2-work-proces-arrow.png" alt="" aria-hidden="true">
                        </div>

                        <div class="work-process-card two">
                            <div class="sl-no">
                                <span>Step</span>
                                <h3>2</h3>
                            </div>
                            <div class="icon">
                                <lord-icon
                                    src="https://cdn.lordicon.com/lklbeydf.json"
                                    trigger="hover"
                                    colors="primary:#e83a30,secondary:#e83a30"
                                    style="width:150px;height:150px">
                                </lord-icon>
                            </div>
                            <div class="work-content text-center">
                                <h3>Book Your Day</h3>
                                <p>Create your free account, add your pets and pick a time that suits you. We'll confirm your booking by email.</p>
                            </div>
                        </div>

                        <div class="work-proces-arrow">
                            <img class="img-fluid" src="assets/images/bg/h2-work-proces-arrow.png" alt="" aria-hidden="true">
                        </div>

                        <div class="work-process-card three">
                            <div class="sl-no">
                                <span>Step</span>
                                <h3>3</h3>
                            </div>
                            <div class="icon">
                                <lord-icon
                                    src="https://cdn.lordicon.com/slctsmek.json"
                                    trigger="hover"
                                    colors="primary:#e83a30,secondary:#e83a30"
                                    style="width:150px;height:150px">
                                </lord-icon>
                            </div>
                            <div class="work-content text-center">
                                <h3>Relax</h3>
                                <p>Relax and trust us at Something About Pets. Our dedicated team ensures your pet's excellent care, giving you peace of 
                                    mind for a worry-free day.</p>
                            </div>
                        </div>
                    </div>
                    
                    
                </div>
            </div>
        </div>
    </section>

    <!-- ========== H2 Working Process Area End============= -->
    
    <!-- ========== H2 Contact Area Start============= -->

    <section class="h2-contact-area mb-120" id="contact">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="contact-wrap">
                                <div class="section-title2">
                                    <h2>Contact</h2>
                                </div>
                                <form class="sap-ajax-form" action="contact.php" method="post" novalidate>
                                    <?= csrf_field() ?>
                                    <?= bot_fields() ?>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-inner user">
                                                <label class="visually-hidden" for="c-name">Your name</label>
                                                <input id="c-name" type="text" name="name" placeholder="Enter your name" required maxlength="120" autocomplete="name" value="<?= e(old('name', $user['name'] ?? '')) ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-inner email">
                                                <label class="visually-hidden" for="c-email">Your email</label>
                                                <input id="c-email" type="email" name="email" placeholder="Enter your email" required maxlength="190" autocomplete="email" value="<?= e(old('email', $user['email'] ?? '')) ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-inner querry">
                                                <label class="visually-hidden" for="c-subject">Subject</label>
                                                <input id="c-subject" type="text" name="subject" placeholder="Subject" maxlength="190" value="<?= e(old('subject')) ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-inner">
                                                <label class="visually-hidden" for="c-message">Your message</label>
                                                <textarea id="c-message" name="message" placeholder="Your message" required maxlength="5000"><?= e(old('message')) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="sap-form-status" role="status" aria-live="polite"><?= $contactFlash ?></div>
                                            <div class="form-inner">
                                                <button class="primary-btn3" type="submit">Send Now</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="contact-img">
                                <img class="img-fluid" src="assets/images/bg/h2-contact-hero-526x677.jpg" alt="A dog walker with a golden retriever" loading="lazy" width="526" height="677">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="section-title2">
                                <h2>FAQ</h2>
                            </div>
                            <div class="accordion" id="accordionExample">
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="headingOne">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                            01. How do I book?
                                        </button>
                                    </h3>
                                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne"
                                        data-bs-parent="#accordionExample">
                                        <div class="accordion-body">Create a free account, add your pets, then choose a service and a time on our booking page. Your request is held for you straight away and we'll email you as soon as it's confirmed.</div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            02. Can we meet before the first booking?
                                        </button>
                                    </h3>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                                        data-bs-parent="#accordionExample">
                                        <div class="accordion-body">Yes, we'd love to. Send us a message and we'll arrange a meet and greet so your pet can get to know us and we can learn their routine, feeding and any medication needs.</div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                            03. Which areas of Auckland do you cover?
                                        </button>
                                    </h3>
                                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree"
                                        data-bs-parent="#accordionExample">
                                        <div class="accordion-body">We're based in Auckland. Get in touch with your suburb and we'll let you know if we can help.</div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="headingFour">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                            04. How do I cancel or change a booking?
                                        </button>
                                    </h3>
                                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour"
                                        data-bs-parent="#accordionExample">
                                        <div class="accordion-body">You can cancel online from My Account up to 24 hours before the booking starts. For anything closer to the time, or to change a booking, just contact us.</div>
                                    </div>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== H2 Contact Area End============= -->

    <!-- ========== H2 Testimonial Area Start============= -->

    <section class="h2-testimonial-area mb-120" id="reviews">
        <div class="container">
            <div class="row mb-60">
                <div class="col-lg-12">
                    <div class="section-title2 text-center">
                        <h2>What Our Customers Say</h2>
                    </div>
                </div>
            </div>
            <div class="row mb-50">
                <div class="swiper h2-testimonial-slider">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="testimonial-wrap">
                                <div class="testimonial-content text-center">
                                    <div class="quat-icon">
                                        <img class="left-quat" src="assets/images/icon/left-quat.svg" alt="">
                                        <img class="right-quat" src="assets/images/icon/right-quat.svg" alt="">
                                    </div>
                                    <div class="foot-vector">
                                        <img class="testimonial-vec-left" src="assets/images/icon/h2-testimonial-vec-left.svg" alt="">
                                        <img class="testimonial-vec-right" src="assets/images/icon/h2-testimonial-vec-right.svg" alt="">
                                    </div>
                                    <div class="author-name-deg">
                                        <h3>Jonny</h3>
                                        <h4>Kohimarama - Auckland</h4>
                                        <h5>4 Year Old Staffy - Pet-sit/House-sit</h5>
                                        <span>Mack's Owner</span>
                                    </div>
                                    <p>I cannot recommend Jess enough. She is always my first choice for a house sitter and dog walker. Mack is her best friend.</p>
                                    <div class="review">
                                        <ul>
                                            <li><i class="bi bi-star-fill"></i></li>
                                            <li><i class="bi bi-star-fill"></i></li>
                                            <li><i class="bi bi-star-fill"></i></li>
                                            <li><i class="bi bi-star-fill"></i></li>
                                            <li><i class="bi bi-star-fill"></i></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="testimonial-img">
                                    <img src="assets/images/bg/h2-testimonial-1-106x106.jpg" alt="Mack, a Staffy" loading="lazy" width="106" height="106">
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="testimonial-wrap">
                                <div class="testimonial-content text-center">
                                    <div class="quat-icon">
                                        <img class="left-quat" src="assets/images/icon/left-quat.svg" alt="">
                                        <img class="right-quat" src="assets/images/icon/right-quat.svg" alt="">
                                    </div>
                                    <div class="foot-vector">
                                        <img class="testimonial-vec-left" src="assets/images/icon/h2-testimonial-vec-left.svg" alt="">
                                        <img class="testimonial-vec-right" src="assets/images/icon/h2-testimonial-vec-right.svg" alt="">
                                    </div>
                                    <div class="author-name-deg">
                                        <h3>Nicole</h3>
                                        <h4>Glendowie - Auckland</h4>
                                        <h5>8 Year Old Weimaraner & 3 Year Old Cat - Pet-sit/House-sit [5 weeks]</h5>
                                        <span>Elsie & Maui's Owner</span>
                                    </div>
                                    <p>Jessica and Something About Pets are top-notch house sitters. Always reliable, she kept us updated, left the house spotless, 
                                        and went above and beyond to ensure our pets were happy. Highly recommended!</p>
                                    <div class="review">
                                        <ul>
                                            <li><i class="bi bi-star-fill"></i></li>
                                            <li><i class="bi bi-star-fill"></i></li>
                                            <li><i class="bi bi-star-fill"></i></li>
                                            <li><i class="bi bi-star-fill"></i></li>
                                            <li><i class="bi bi-star-fill"></i></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="testimonial-img">
                                    <img src="assets/images/bg/h2-testimonial-2-106x106.jpg" alt="Elsie and Maui" loading="lazy" width="106" height="106">
                                </div>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="testimonial-wrap">
                                <div class="testimonial-content text-center">
                                    <div class="quat-icon">
                                        <img class="left-quat" src="assets/images/icon/left-quat.svg" alt="">
                                        <img class="right-quat" src="assets/images/icon/right-quat.svg" alt="">
                                    </div>
                                    <div class="foot-vector">
                                        <img class="testimonial-vec-left" src="assets/images/icon/h2-testimonial-vec-left.svg" alt="">
                                        <img class="testimonial-vec-right" src="assets/images/icon/h2-testimonial-vec-right.svg" alt="">
                                    </div>
                                    <div class="author-name-deg">
                                        <h3>Adam</h3>
                                        <h4>Kohimarama - Auckland</h4>
                                        <h5>2 Griffons - Dog Walking</h5>
                                        <span>Peter & Donnie's Owner</span>
                                    </div>
                                    <p>Jessica's swift communication and warm reception by Pete and Donnie were outstanding. They cherish their walks with 
                                        her, and her timely updates make her highly recommended. Peter and Donnie agree!</p>
                                    <div class="review">
                                        <ul>
                                            <li><i class="bi bi-star-fill"></i></li>
                                            <li><i class="bi bi-star-fill"></i></li>
                                            <li><i class="bi bi-star-fill"></i></li>
                                            <li><i class="bi bi-star-fill"></i></li>
                                            <li><i class="bi bi-star-fill"></i></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="testimonial-img">
                                    <img src="assets/images/bg/h2-testimonial-3-106x106.jpg" alt="Peter and Donnie, two Griffons" loading="lazy" width="106" height="106">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 d-flex justify-content-between">
                    <button type="button" class="slider-btn prev-btn-5" aria-label="Previous review">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div class="swiper-scrollbar"></div>
                    
                    <button type="button" class="slider-btn next-btn-5" aria-label="Next review">
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== H2 Testimonial Area End============= -->

<?php
clear_old();
layout_end();
