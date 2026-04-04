<?php
  $apkPath = "downloads/bettergym.apk";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BetterGYM | AI Workout Form & Risk Analysis</title>
  <meta
    name="description"
    content="BetterGYM is an AI-powered fitness assistant that counts reps, checks workout form, and analyzes injury risk using pose estimation and 2s-AGCN."
  />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="bg-glow"></div>
  <div class="grid-overlay"></div>

  <header class="site-header">
    <div class="container nav">
      <a href="#home" class="logo">
        <span class="logo-mark">B</span>
        <span>BetterGYM</span>
      </a>

      <nav class="nav-links">
        <a href="#features">Features</a>
        <a href="#how-it-works">How it Works</a>
        <a href="#about">About</a>
      </nav>

      <a href="<?php echo $apkPath; ?>" class="btn btn-outline" download>Download App</a>

      <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>

    <div class="mobile-menu" id="mobileMenu">
      <a href="#features">Features</a>
      <a href="#how-it-works">How it Works</a>
      <a href="#about">About</a>
      <a href="<?php echo $apkPath; ?>" download>Download App</a>
    </div>
  </header>

  <main>
    <section class="hero section" id="home">
      <div class="container hero-grid">
        <div class="hero-copy reveal">
          <p class="eyebrow">AI-Powered Fitness Assistant</p>
          <h1>
            Train smarter with
            <span class="gradient-text">real-time form correction</span>
            and predictive risk analysis.
          </h1>
          <p class="hero-text">
            BetterGYM uses pose estimation and intelligent movement analysis to count reps,
            evaluate exercise form, and detect possible injury risk before it gets worse.
          </p>

          <div class="hero-actions">
            <a href="<?php echo $apkPath; ?>" class="btn btn-primary" download>Download APK</a>
            <a href="#how-it-works" class="btn btn-ghost">See How It Works</a>
          </div>

          <div class="hero-stats">
            <div class="stat-card">
              <strong>Real-Time</strong>
              <span>Pose Tracking</span>
            </div>
            <div class="stat-card">
              <strong>Smart</strong>
              <span>Rep Counting</span>
            </div>
            <div class="stat-card">
              <strong>AI</strong>
              <span>Risk Detection</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="features">
      <div class="container">
        <div class="section-heading reveal">
          <p class="eyebrow">Features</p>
          <h2>Built to support safer and smarter workouts</h2>
          <p>
            BetterGYM combines computer vision, form analysis, and movement intelligence
            in one fitness experience.
          </p>
        </div>

        <div class="features-grid">
          <article class="feature-card reveal">
            <div class="icon">01</div>
            <h3>Real-Time Pose Detection</h3>
            <p>
              Tracks body keypoints during exercise to understand posture and movement
              as you perform each repetition.
            </p>
          </article>

          <article class="feature-card reveal delay-1">
            <div class="icon">02</div>
            <h3>Automatic Rep Counting</h3>
            <p>
              Monitors exercise motion to count repetitions accurately without requiring
              manual input from the user.
            </p>
          </article>

          <article class="feature-card reveal delay-2">
            <div class="icon">03</div>
            <h3>Form Assessment</h3>
            <p>
              Evaluates exercise execution and highlights movement patterns that may
              indicate poor form or imbalance.
            </p>
          </article>

          <article class="feature-card reveal">
            <div class="icon">04</div>
            <h3>Predictive Risk Analysis</h3>
            <p>
              Uses AI-based sequence analysis to classify workout movement and estimate
              potential injury risk.
            </p>
          </article>

          <article class="feature-card reveal delay-1">
            <div class="icon">05</div>
            <h3>Workout Feedback</h3>
            <p>
              Gives users understandable insights so they can adjust posture and improve
              technique over time.
            </p>
          </article>

          <article class="feature-card reveal delay-2">
            <div class="icon">06</div>
            <h3>Mobile-Friendly Experience</h3>
            <p>
              Designed for a smartphone-based workflow, making guided fitness analysis
              accessible and practical.
            </p>
          </article>
        </div>
      </div>
    </section>

    <section class="section" id="how-it-works">
      <div class="container">
        <div class="section-heading reveal">
          <p class="eyebrow">How It Works</p>
          <h2>From camera input to intelligent workout insights</h2>
        </div>

        <div class="timeline">
          <div class="timeline-item reveal">
            <span class="step">1</span>
            <div>
              <h3>Capture Movement</h3>
              <p>The camera records the user while performing an exercise routine.</p>
            </div>
          </div>

          <div class="timeline-item reveal delay-1">
            <span class="step">2</span>
            <div>
              <h3>Extract Keypoints</h3>
              <p>Body joints are detected and converted into skeletal keypoint data.</p>
            </div>
          </div>

          <div class="timeline-item reveal delay-2">
            <span class="step">3</span>
            <div>
              <h3>Analyze Form and Reps</h3>
              <p>Exercise posture and repetition patterns are evaluated in real time.</p>
            </div>
          </div>

          <div class="timeline-item reveal">
            <span class="step">4</span>
            <div>
              <h3>Predict Risk</h3>
              <p>Movement sequences are processed to assess possible injury-related risk.</p>
            </div>
          </div>

          <div class="timeline-item reveal delay-1">
            <span class="step">5</span>
            <div>
              <h3>Deliver Feedback</h3>
              <p>The app shows results clearly so the user can improve technique and safety.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="about">
      <div class="container about-grid">
        <div class="about-card reveal">
          <p class="eyebrow">Why BetterGYM</p>
          <h2>Not just counting reps — understanding movement quality</h2>
          <p>
            BetterGYM was designed as a smarter workout assistant that goes beyond
            basic activity tracking. It focuses on how exercises are performed, helping
            users build better habits while reducing unsafe movement patterns.
          </p>
        </div>

        <div class="about-card glass reveal delay-1">
          <h3>Core Value</h3>
          <p>
            BetterGYM aims to make workout guidance more accessible through AI-driven
            posture recognition, rep monitoring, and predictive analysis.
          </p>
          <ul class="check-list">
            <li>Smarter exercise monitoring</li>
            <li>Simple, readable feedback</li>
            <li>Safer workout support</li>
          </ul>
        </div>
      </div>
    </section>

    <section class="section cta-section" id="cta">
      <div class="container cta-box reveal">
        <p class="eyebrow">Start Smarter Training</p>
        <h2>Bring AI assistance into your workout routine</h2>
        <p>
          BetterGYM helps users train with more awareness by combining visual tracking,
          form correction, repetition counting, and predictive movement analysis.
        </p>
        <a href="<?php echo $apkPath; ?>" class="btn btn-primary" download>Download APK</a>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-content">
      <p>© <?php echo date('Y'); ?> BetterGYM. All rights reserved.</p>
      <p>by Strawcas, Takkimi, Stephen. Pakyu Abel T. Soliman</p>
    </div>
  </footer>

  <script src="script.js"></script>
</body>
</html>