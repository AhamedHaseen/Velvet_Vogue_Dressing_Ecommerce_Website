<?php
// Include session and database for navigation
include 'includes/session_db.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include 'includes/customer_head.php'; ?>
    <title>About Us - Velvet Vogue</title>
  </head>

  <body>
    <?php include 'includes/customer_navbar.php'; ?>

    <!-- Page Header -->
    <section class="bg-light py-5">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                  <a href="index.php" class="text-decoration-none">
                    <i class="bx bx-home me-1"></i>Home
                  </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                  About Us
                </li>
              </ol>
            </nav>

            <!-- Page Title -->
            <div class="text-center">
              <h1 class="display-4 fw-bold text-dark mb-3">
                <i class="bx bx-diamond me-3 text-primary"></i>About Velvet
                Vogue
              </h1>
              <p class="lead text-muted mb-0">
                Discover our story, values, and commitment to exceptional
                fashion
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Hero Story Section -->
    <section class="py-5">
      <div class="container">
        <div class="row align-items-center g-5">
          <div class="col-lg-6">
            <div class="about-content">
              <h2 class="display-5 fw-bold mb-4">
                Fashion That Speaks<br />
                <span class="text-primary">Your Language</span>
              </h2>
              <p class="lead mb-4">
                Since 2015, Velvet Vogue has been more than just a fashion brand
                – we're storytellers, weaving narratives of self-expression
                through every thread, every design, and every collection.
              </p>
              <p class="text-muted mb-4">
                Born from a passion for accessible luxury and sustainable
                fashion, we believe that style shouldn't be a privilege. It
                should be a celebration of who you are, where you're going, and
                the confidence that comes from wearing something that truly
                represents you.
              </p>
              <div class="d-flex flex-wrap gap-3">
                <div class="badge-stat">
                  <h3 class="text-primary fw-bold mb-0">10+</h3>
                  <small class="text-muted">Years of Excellence</small>
                </div>
                <div class="badge-stat">
                  <h3 class="text-primary fw-bold mb-0">50K+</h3>
                  <small class="text-muted">Happy Customers</small>
                </div>
                <div class="badge-stat">
                  <h3 class="text-primary fw-bold mb-0">1000+</h3>
                  <small class="text-muted">Unique Designs</small>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="about-image">
              <img
                src="./Images/si_1.jpg"
                alt="Velvet Vogue Fashion Collection"
                class="img-fluid rounded-3 shadow-lg"
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Values Section -->
    <section class="py-5 bg-light">
      <div class="container">
        <div class="row">
          <div class="col-12 text-center mb-5">
            <h2 class="display-6 fw-bold mb-3">Our Core Values</h2>
            <p class="lead text-muted">
              The principles that guide everything we do at Velvet Vogue
            </p>
          </div>
        </div>
        <div class="row g-4">
          <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm value-card">
              <div class="card-body text-center p-4">
                <div class="value-icon mb-3">
                  <i class="bx bx-leaf fs-1 text-success"></i>
                </div>
                <h5 class="fw-bold mb-3">Sustainability</h5>
                <p class="text-muted">
                  We're committed to ethical fashion practices, using
                  eco-friendly materials and supporting fair trade. Every
                  purchase helps build a more sustainable future for fashion.
                </p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm value-card">
              <div class="card-body text-center p-4">
                <div class="value-icon mb-3">
                  <i class="bx bx-diamond fs-1 text-primary"></i>
                </div>
                <h5 class="fw-bold mb-3">Quality Excellence</h5>
                <p class="text-muted">
                  From fabric selection to final stitching, we maintain the
                  highest standards of craftsmanship. Quality isn't just what we
                  do – it's who we are.
                </p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm value-card">
              <div class="card-body text-center p-4">
                <div class="value-icon mb-3">
                  <i class="bx bx-group fs-1 text-warning"></i>
                </div>
                <h5 class="fw-bold mb-3">Inclusivity</h5>
                <p class="text-muted">
                  Fashion is for everyone. We create designs that celebrate
                  diversity and offer sizes and styles that make everyone feel
                  confident and beautiful.
                </p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm value-card">
              <div class="card-body text-center p-4">
                <div class="value-icon mb-3">
                  <i class="bx bx-bulb fs-1 text-info"></i>
                </div>
                <h5 class="fw-bold mb-3">Innovation</h5>
                <p class="text-muted">
                  We're always pushing boundaries, experimenting with new
                  designs, technologies, and sustainable materials to bring you
                  the future of fashion.
                </p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm value-card">
              <div class="card-body text-center p-4">
                <div class="value-icon mb-3">
                  <i class="bx bx-heart fs-1 text-danger"></i>
                </div>
                <h5 class="fw-bold mb-3">Customer Care</h5>
                <p class="text-muted">
                  Your satisfaction is our priority. From personalized styling
                  advice to hassle-free returns, we're here to make your
                  shopping experience exceptional.
                </p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm value-card">
              <div class="card-body text-center p-4">
                <div class="value-icon mb-3">
                  <i class="bx bx-trending-up fs-1 text-secondary"></i>
                </div>
                <h5 class="fw-bold mb-3">Trendsetting</h5>
                <p class="text-muted">
                  We don't just follow trends – we create them. Our design team
                  stays ahead of the curve to bring you tomorrow's fashion
                  today.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Team Section -->
    <section class="py-5">
      <div class="container">
        <div class="row">
          <div class="col-12 text-center mb-5">
            <h2 class="display-6 fw-bold mb-3">Meet Our Team</h2>
            <p class="lead text-muted">
              The passionate people behind Velvet Vogue's success
            </p>
          </div>
        </div>
        <div class="row g-4">
          <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm team-card">
              <div class="card-body text-center p-4">
                <div class="team-avatar mb-3">
                  <img
                    src="./Images/si_2.jpg"
                    alt="Sofia Chen - Founder & CEO"
                    class="rounded-circle img-fluid"
                    style="width: 120px; height: 120px; object-fit: cover"
                  />
                </div>
                <h5 class="fw-bold mb-1">Sofia Chen</h5>
                <p class="text-primary mb-3">Founder & CEO</p>
                <p class="text-muted small">
                  Visionary leader with 15+ years in fashion industry. Sofia's
                  passion for sustainable luxury drives Velvet Vogue's mission
                  forward.
                </p>
                <div class="social-links">
                  <a href="#" class="btn btn-outline-primary btn-sm me-2">
                    <i class="bx bxl-linkedin"></i>
                  </a>
                  <a href="#" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bxl-twitter"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm team-card">
              <div class="card-body text-center p-4">
                <div class="team-avatar mb-3">
                  <img
                    src="./Images/si_3.jpg"
                    alt="Marcus Rodriguez - Creative Director"
                    class="rounded-circle img-fluid"
                    style="width: 120px; height: 120px; object-fit: cover"
                  />
                </div>
                <h5 class="fw-bold mb-1">Marcus Rodriguez</h5>
                <p class="text-primary mb-3">Creative Director</p>
                <p class="text-muted small">
                  Award-winning designer who brings innovative concepts to life.
                  Marcus ensures every collection tells a compelling story.
                </p>
                <div class="social-links">
                  <a href="#" class="btn btn-outline-primary btn-sm me-2">
                    <i class="bx bxl-linkedin"></i>
                  </a>
                  <a href="#" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bxl-instagram"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm team-card">
              <div class="card-body text-center p-4">
                <div class="team-avatar mb-3">
                  <img
                    src="./Images/si_4.jpg"
                    alt="Emma Thompson - Head of Sustainability"
                    class="rounded-circle img-fluid"
                    style="width: 120px; height: 120px; object-fit: cover"
                  />
                </div>
                <h5 class="fw-bold mb-1">Emma Thompson</h5>
                <p class="text-primary mb-3">Head of Sustainability</p>
                <p class="text-muted small">
                  Environmental advocate leading our green initiatives. Emma
                  ensures our fashion choices benefit both people and planet.
                </p>
                <div class="social-links">
                  <a href="#" class="btn btn-outline-primary btn-sm me-2">
                    <i class="bx bxl-linkedin"></i>
                  </a>
                  <a href="#" class="btn btn-outline-success btn-sm">
                    <i class="bx bx-leaf"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Mission & Vision Section -->
    <section class="py-5 bg-light">
      <div class="container">
        <div class="row g-5">
          <div class="col-lg-6">
            <div class="mission-card">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-5">
                  <div class="d-flex align-items-center mb-4">
                    <div class="icon-wrapper me-3">
                      <i class="bx bx-target-lock fs-1 text-primary"></i>
                    </div>
                    <h3 class="fw-bold mb-0">Our Mission</h3>
                  </div>
                  <p class="text-muted mb-4">
                    To democratize fashion by creating accessible, sustainable,
                    and stylish clothing that empowers individuals to express
                    their unique identity with confidence.
                  </p>
                  <ul class="list-unstyled">
                    <li class="d-flex align-items-center mb-2">
                      <i class="bx bx-check-circle text-success me-3"></i>
                      Make quality fashion accessible to everyone
                    </li>
                    <li class="d-flex align-items-center mb-2">
                      <i class="bx bx-check-circle text-success me-3"></i>
                      Promote sustainable and ethical practices
                    </li>
                    <li class="d-flex align-items-center mb-2">
                      <i class="bx bx-check-circle text-success me-3"></i>
                      Celebrate diversity and individual expression
                    </li>
                    <li class="d-flex align-items-center">
                      <i class="bx bx-check-circle text-success me-3"></i>
                      Foster a community of fashion enthusiasts
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="vision-card">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-5">
                  <div class="d-flex align-items-center mb-4">
                    <div class="icon-wrapper me-3">
                      <i class="bx bx-rocket fs-1 text-primary"></i>
                    </div>
                    <h3 class="fw-bold mb-0">Our Vision</h3>
                  </div>
                  <p class="text-muted mb-4">
                    To be the world's leading sustainable fashion brand,
                    inspiring a generation to choose style that doesn't
                    compromise on values, quality, or the planet's future.
                  </p>
                  <div class="row g-3">
                    <div class="col-6">
                      <div class="stat-item text-center">
                        <h4 class="text-primary fw-bold">2030</h4>
                        <small class="text-muted">Carbon Neutral Goal</small>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="stat-item text-center">
                        <h4 class="text-primary fw-bold">100%</h4>
                        <small class="text-muted">Sustainable Materials</small>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="stat-item text-center">
                        <h4 class="text-primary fw-bold">Global</h4>
                        <small class="text-muted">Market Presence</small>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="stat-item text-center">
                        <h4 class="text-primary fw-bold">1M+</h4>
                        <small class="text-muted">Community Members</small>
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

    <!-- Timeline Section -->
    <section class="py-5">
      <div class="container">
        <div class="row">
          <div class="col-12 text-center mb-5">
            <h2 class="display-6 fw-bold mb-3">Our Journey</h2>
            <p class="lead text-muted">
              Milestones that shaped Velvet Vogue's evolution
            </p>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-8 mx-auto">
            <div class="timeline">
              <div class="timeline-item">
                <div class="timeline-marker">
                  <i class="bx bx-star text-primary"></i>
                </div>
                <div class="timeline-content">
                  <h5 class="fw-bold">2015 - The Beginning</h5>
                  <p class="text-muted">
                    Sofia Chen launches Velvet Vogue with a small collection of
                    sustainable basics, operating from a shared studio space.
                  </p>
                </div>
              </div>
              <div class="timeline-item">
                <div class="timeline-marker">
                  <i class="bx bx-trophy text-success"></i>
                </div>
                <div class="timeline-content">
                  <h5 class="fw-bold">2017 - First Award</h5>
                  <p class="text-muted">
                    Recognized as "Best Sustainable Fashion Startup" at the
                    Global Fashion Innovation Awards.
                  </p>
                </div>
              </div>
              <div class="timeline-item">
                <div class="timeline-marker">
                  <i class="bx bx-store text-info"></i>
                </div>
                <div class="timeline-content">
                  <h5 class="fw-bold">2019 - First Flagship Store</h5>
                  <p class="text-muted">
                    Opens our first flagship store in Style City, creating an
                    immersive brand experience for customers.
                  </p>
                </div>
              </div>
              <div class="timeline-item">
                <div class="timeline-marker">
                  <i class="bx bx-world text-warning"></i>
                </div>
                <div class="timeline-content">
                  <h5 class="fw-bold">2021 - Global Expansion</h5>
                  <p class="text-muted">
                    Launches international shipping and partnerships with
                    sustainable fashion retailers worldwide.
                  </p>
                </div>
              </div>
              <div class="timeline-item">
                <div class="timeline-marker">
                  <i class="bx bx-leaf text-success"></i>
                </div>
                <div class="timeline-content">
                  <h5 class="fw-bold">2023 - Zero Waste Initiative</h5>
                  <p class="text-muted">
                    Achieves zero waste in manufacturing and launches the
                    circular fashion program for garment recycling.
                  </p>
                </div>
              </div>
              <div class="timeline-item">
                <div class="timeline-marker">
                  <i class="bx bx-rocket text-primary"></i>
                </div>
                <div class="timeline-content">
                  <h5 class="fw-bold">2025 - Innovation Lab</h5>
                  <p class="text-muted">
                    Opens the Velvet Vogue Innovation Lab, pioneering new
                    sustainable materials and smart fashion technologies.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Call to Action Section -->
    <section class="py-5 bg-primary text-white">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <h2 class="display-6 fw-bold mb-3">Join Our Fashion Revolution</h2>
            <p class="lead mb-4 opacity-75">
              Be part of a community that believes fashion should be beautiful,
              sustainable, and accessible. Discover your style with Velvet
              Vogue.
            </p>
          </div>
          <div class="col-lg-4 text-lg-end">
            <a href="featureProductView.php" class="btn btn-light btn-lg me-3">
              <i class="bx bx-shopping-bag me-2"></i>Shop Now
            </a>
            <a href="contact.php" class="btn btn-outline-light btn-lg">
              <i class="bx bx-message me-2"></i>Get In Touch
            </a>
          </div>
        </div>
      </div>
    </section>

    <?php include 'includes/customer_footer.php'; ?>

    <?php include 'includes/customer_scripts.php'; ?>

    <!-- About Page JavaScript -->
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        // Newsletter form
        const newsletterForm = document.getElementById("footerNewsletterForm");
        if (newsletterForm) {
          newsletterForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            if (email) {
              alert("Thank you for subscribing to our newsletter!");
              this.reset();
            }
          });
        }

        // Animate cards on scroll
        const observerOptions = {
          threshold: 0.1,
          rootMargin: "0px 0px -50px 0px",
        };

        const observer = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.style.opacity = "1";
              entry.target.style.transform = "translateY(0)";
            }
          });
        }, observerOptions);

        // Observe all cards
        document
          .querySelectorAll(
            ".value-card, .team-card, .mission-card, .vision-card"
          )
          .forEach((card) => {
            card.style.opacity = "0";
            card.style.transform = "translateY(20px)";
            card.style.transition = "opacity 0.6s ease, transform 0.6s ease";
            observer.observe(card);
          });

        // Timeline animation
        const timelineItems = document.querySelectorAll(".timeline-item");
        timelineItems.forEach((item, index) => {
          item.style.opacity = "0";
          item.style.transform = "translateX(-20px)";
          item.style.transition = `opacity 0.6s ease ${
            index * 0.1
          }s, transform 0.6s ease ${index * 0.1}s`;
          observer.observe(item);
        });
      });
    </script>

    <!-- Custom CSS for About Page -->
    <style>
      .value-card,
      .team-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }

      .value-card:hover,
      .team-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
      }

      .value-icon,
      .team-avatar {
        transition: transform 0.3s ease;
      }

      .value-card:hover .value-icon {
        transform: scale(1.1);
      }

      .badge-stat {
        text-align: center;
        padding: 1rem;
        background: rgba(13, 110, 253, 0.1);
        border-radius: 0.5rem;
      }

      .timeline {
        position: relative;
        padding-left: 2rem;
      }

      .timeline::before {
        content: "";
        position: absolute;
        left: 1rem;
        top: 0;
        height: 100%;
        width: 2px;
        background: linear-gradient(to bottom, #0d6efd, #6f42c1);
      }

      .timeline-item {
        position: relative;
        margin-bottom: 2rem;
      }

      .timeline-marker {
        position: absolute;
        left: -2.5rem;
        top: 0;
        width: 2.5rem;
        height: 2.5rem;
        background: white;
        border: 3px solid #0d6efd;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
      }

      .timeline-content {
        background: white;
        padding: 1.5rem;
        border-radius: 0.75rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-left: 1rem;
      }

      .about-image img {
        transition: transform 0.3s ease;
      }

      .about-image:hover img {
        transform: scale(1.05);
      }

      .stat-item {
        padding: 1rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
      }

      @media (max-width: 768px) {
        .timeline {
          padding-left: 1rem;
        }

        .timeline::before {
          left: 0.5rem;
        }

        .timeline-marker {
          left: -1.75rem;
          width: 2rem;
          height: 2rem;
        }

        .timeline-content {
          margin-left: 0.5rem;
        }

        .badge-stat {
          margin-bottom: 1rem;
        }
      }
    </style>
  </body>
</html>

