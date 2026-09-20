<?php
require_once __DIR__ . '/includes/functions.php';

// Already logged in? Skip the landing page.
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SkillSwap &ndash; Student Skill Exchange Platform</title>

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet" />

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ["Inter", "sans-serif"],
            display: ["Sora", "sans-serif"]
          },
          colors: {
            brand: {
              50:  "#eef5ff", 100: "#d9e9ff", 200: "#bcd5ff",
              300: "#8eb8ff", 400: "#598eff", 500: "#3366ff",
              600: "#1a47f5", 700: "#1336e0", 800: "#162db6",
              900: "#182b8f", 950: "#131d5c"
            },
            accent: { 400: "#fb923c", 500: "#f97316", 600: "#ea6d0e" },
            surface: "#f8fafc"
          },
          boxShadow: {
            card: "0 4px 24px 0 rgba(51,102,255,0.08)",
            hero: "0 8px 40px 0 rgba(51,102,255,0.18)"
          }
        }
      }
    };
  </script>

  <style>
    body { font-family: "Inter", sans-serif; }
    h1, h2, h3, h4 { font-family: "Sora", sans-serif; }

    .hero-gradient {
      background: linear-gradient(135deg, #182b8f 0%, #3366ff 50%, #598eff 100%);
    }
    .blob {
      position: absolute; border-radius: 50%;
      filter: blur(80px); opacity: .35;
      animation: float 8s ease-in-out infinite alternate;
    }
    @keyframes float {
      from { transform: translateY(0) scale(1); }
      to   { transform: translateY(-30px) scale(1.05); }
    }
    #navbar { transition: box-shadow .3s, background .3s; }
    #navbar.scrolled {
      box-shadow: 0 2px 20px rgba(0,0,0,.10);
      background: rgba(255,255,255,.98);
    }
    .feature-card { transition: transform .25s, box-shadow .25s; }
    .feature-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 36px rgba(51,102,255,.14);
    }
    .badge-pulse { animation: badge-ping 2s cubic-bezier(0,0,0.2,1) infinite; }
    @keyframes badge-ping { 75%,100% { transform: scale(1.5); opacity: 0; } }
    section[id] { scroll-margin-top: 72px; }
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #3366ff; border-radius: 3px; }
    .form-input:focus {
      outline: none; border-color: #3366ff;
      box-shadow: 0 0 0 3px rgba(51,102,255,.15);
    }
    .fade-in {
      opacity: 0; transform: translateY(24px);
      transition: opacity .6s ease, transform .6s ease;
    }
    .fade-in.visible { opacity: 1; transform: translateY(0); }

    /* Hero grid overlay */
    .grid-overlay {
      background-image:
        linear-gradient(rgba(255,255,255,.15) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.15) 1px, transparent 1px);
      background-size: 48px 48px;
    }
  </style>
</head>

<body class="bg-white text-gray-800 antialiased">

<!-- ================================================================
     NAVIGATION
================================================================ -->
<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm border-b border-gray-100">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">

      <!-- Logo -->
      <a href="#home" class="flex items-center gap-2 group">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105"
             style="background:#3366ff;box-shadow:0 8px 40px rgba(51,102,255,.3)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
               stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M8 3H5a2 2 0 0 0-2 2v3"/>
            <path d="M21 8V5a2 2 0 0 0-2-2h-3"/>
            <path d="M3 16v3a2 2 0 0 0 2 2h3"/>
            <path d="M16 21h3a2 2 0 0 0 2-2v-3"/>
            <path d="m7 16 3-3 2 2 3-4 2 5"/>
          </svg>
        </div>
        <span class="text-xl font-bold" style="font-family:Sora,sans-serif;color:#1336e0">
          Skill<span style="color:#f97316">Swap</span>
        </span>
      </a>

      <!-- Desktop links -->
      <div class="hidden md:flex items-center gap-8">
        <a href="#features"     class="nav-link text-sm font-medium text-gray-600 hover:text-blue-600 transition-colors">Features</a>
        <a href="#how-it-works" class="nav-link text-sm font-medium text-gray-600 hover:text-blue-600 transition-colors">How It Works</a>
        <a href="#modules"      class="nav-link text-sm font-medium text-gray-600 hover:text-blue-600 transition-colors">Modules</a>
        <a href="#about"        class="nav-link text-sm font-medium text-gray-600 hover:text-blue-600 transition-colors">About</a>
        <a href="/auth/login.php" class="nav-link text-sm font-medium text-gray-600 hover:text-blue-600 transition-colors">Sign In</a>
      </div>

      <!-- CTA buttons -->
      <div class="hidden md:flex items-center gap-3">
        <a href="/auth/login.php" class="text-sm font-semibold text-blue-600 hover:text-blue-700 transition-colors">Sign In</a>
        <a href="/auth/register.php" class="text-sm font-semibold text-white px-5 py-2 rounded-lg transition-colors"
           style="background:#3366ff" onmouseover="this.style.background='#1a47f5'" onmouseout="this.style.background='#3366ff'">
          Get Started
        </a>
      </div>

      <!-- Hamburger -->
      <button id="menu-btn" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors" aria-label="Toggle menu">
        <i data-lucide="menu" class="w-5 h-5 text-gray-700"></i>
      </button>
    </div>
  </div>

  <!-- Mobile menu -->
  <div id="mobile-menu" class="hidden md:hidden border-t border-gray-100 bg-white px-4 py-4 space-y-3">
    <a href="#features"     class="block text-sm font-medium text-gray-700 hover:text-blue-600 py-1">Features</a>
    <a href="#how-it-works" class="block text-sm font-medium text-gray-700 hover:text-blue-600 py-1">How It Works</a>
    <a href="#modules"      class="block text-sm font-medium text-gray-700 hover:text-blue-600 py-1">Modules</a>
    <a href="#about"        class="block text-sm font-medium text-gray-700 hover:text-blue-600 py-1">About</a>
    <a href="/auth/login.php" class="block text-sm font-medium text-gray-700 hover:text-blue-600 py-1">Sign In</a>
    <div class="pt-2 flex gap-3">
      <a href="/auth/login.php" class="flex-1 text-center text-sm font-semibold border border-blue-500 text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition-colors">Sign In</a>
      <a href="/auth/register.php" class="flex-1 text-center text-sm font-semibold text-white px-4 py-2 rounded-lg" style="background:#3366ff">Get Started</a>
    </div>
  </div>
</nav>


<!-- ================================================================
     HERO
================================================================ -->
<section id="home" class="hero-gradient relative overflow-hidden flex items-center pt-16" style="min-height:100vh">

  <!-- Decorative blobs -->
  <div class="blob" style="width:380px;height:380px;background:#93c5fd;top:40px;left:-80px"></div>
  <div class="blob" style="width:280px;height:280px;background:#818cf8;bottom:40px;right:40px;animation-delay:3s"></div>
  <div class="blob" style="width:220px;height:220px;background:#fb923c;top:120px;right:25%;animation-delay:1.5s;opacity:.2"></div>

  <!-- Grid overlay -->
  <div class="absolute inset-0 grid-overlay opacity-10"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 grid lg:grid-cols-2 gap-12 items-center">

    <!-- Text -->
    <div class="text-white text-center lg:text-left">
      <!-- Live badge -->
      <div class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 mb-6"
           style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2)">
        <span class="relative flex h-2 w-2">
          <span class="badge-pulse absolute inline-flex h-full w-full rounded-full" style="background:#fb923c;opacity:.75"></span>
          <span class="relative inline-flex h-2 w-2 rounded-full" style="background:#f97316"></span>
        </span>
        <span class="text-xs font-semibold tracking-wide uppercase" style="color:rgba(255,255,255,.9)">
          Peer-to-Peer Learning Platform
        </span>
      </div>

      <h1 style="font-family:Sora,sans-serif;font-size:clamp(2.6rem,5.5vw,4.2rem);font-weight:800;line-height:1.1;margin-bottom:1.5rem">
        Exchange Skills,<br/>
        <span style="color:#fb923c">Not Money.</span>
      </h1>

      <p style="font-size:1.125rem;color:#bfdbfe;max-width:38rem;margin-bottom:2.5rem;line-height:1.75">
        SkillSwap connects students to teach and learn from each other &mdash; completely free.
        Share what you know, discover what you don&rsquo;t, and grow together through verified
        peer-to-peer skill exchange.
      </p>

      <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
        <a href="/auth/register.php"
           class="inline-flex items-center justify-center gap-2 text-white font-semibold text-base px-8 py-4 rounded-xl transition-all hover:scale-105"
           style="background:#f97316">
          <i data-lucide="zap" class="w-5 h-5"></i> Start Swapping Skills
        </a>
        <a href="#how-it-works"
           class="inline-flex items-center justify-center gap-2 text-white font-semibold text-base px-8 py-4 rounded-xl transition-all"
           style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3)">
          <i data-lucide="play-circle" class="w-5 h-5"></i> See How It Works
        </a>
      </div>

      <!-- Trust row -->
      <div class="mt-10 flex flex-wrap justify-center lg:justify-start gap-6">
        <span class="flex items-center gap-2 text-sm" style="color:#bfdbfe">
          <i data-lucide="shield-check" class="w-4 h-4" style="color:#fb923c"></i> Verified Skills
        </span>
        <span class="flex items-center gap-2 text-sm" style="color:#bfdbfe">
          <i data-lucide="users" class="w-4 h-4" style="color:#fb923c"></i> Peer-to-Peer Learning
        </span>
        <span class="flex items-center gap-2 text-sm" style="color:#bfdbfe">
          <i data-lucide="star" class="w-4 h-4" style="color:#fb923c"></i> Ratings &amp; Reviews
        </span>
      </div>
    </div>

    <!-- Hero card (desktop only) -->
    <div class="hidden lg:block">
      <div class="relative">
        <div class="rounded-2xl p-6"
             style="background:rgba(255,255,255,.1);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.2)">

          <div class="flex items-center justify-between mb-5">
            <div>
              <p style="color:rgba(255,255,255,.6);font-size:.75rem;font-weight:500;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem">
                Skill Exchange
              </p>
              <p class="text-white text-lg font-semibold">Find Your Learning Partner</p>
            </div>
            <span class="text-xs font-semibold px-3 py-1 rounded-full"
                  style="background:rgba(74,222,128,.2);color:#86efac;border:1px solid rgba(74,222,128,.3)">Live</span>
          </div>

          <!-- Match cards -->
          <div style="display:flex;flex-direction:column;gap:.75rem">

            <div class="flex items-center gap-4 rounded-xl p-4 cursor-pointer transition-colors"
                 style="background:rgba(255,255,255,.1)" onmouseover="this.style.background='rgba(255,255,255,.18)'" onmouseout="this.style.background='rgba(255,255,255,.1)'">
              <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-white" style="background:#60a5fa">A</div>
              <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-semibold">Arjun Sharma</p>
                <p style="color:#bfdbfe;font-size:.75rem">Teaches: Python &amp; ML</p>
              </div>
              <span class="text-xs px-2 py-1 rounded-full flex-shrink-0"
                    style="background:rgba(249,115,22,.3);color:#fdba74;border:1px solid rgba(249,115,22,.4)">98% match</span>
            </div>

            <div class="flex items-center gap-4 rounded-xl p-4 cursor-pointer transition-colors"
                 style="background:rgba(255,255,255,.1)" onmouseover="this.style.background='rgba(255,255,255,.18)'" onmouseout="this.style.background='rgba(255,255,255,.1)'">
              <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-white" style="background:#a78bfa">P</div>
              <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-semibold">Priya Nair</p>
                <p style="color:#bfdbfe;font-size:.75rem">Teaches: Graphic Design</p>
              </div>
              <span class="text-xs px-2 py-1 rounded-full flex-shrink-0"
                    style="background:rgba(249,115,22,.3);color:#fdba74;border:1px solid rgba(249,115,22,.4)">91% match</span>
            </div>

            <div class="flex items-center gap-4 rounded-xl p-4 cursor-pointer transition-colors"
                 style="background:rgba(255,255,255,.1)" onmouseover="this.style.background='rgba(255,255,255,.18)'" onmouseout="this.style.background='rgba(255,255,255,.1)'">
              <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-white" style="background:#34d399">R</div>
              <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-semibold">Rahul Verma</p>
                <p style="color:#bfdbfe;font-size:.75rem">Teaches: Public Speaking</p>
              </div>
              <span class="text-xs px-2 py-1 rounded-full flex-shrink-0"
                    style="background:rgba(249,115,22,.3);color:#fdba74;border:1px solid rgba(249,115,22,.4)">85% match</span>
            </div>
          </div>

          <button class="mt-4 w-full text-white text-sm font-semibold py-3 rounded-xl transition-colors"
                  style="background:#3366ff" onmouseover="this.style.background='#1a47f5'" onmouseout="this.style.background='#3366ff'">
            View All Matches &rarr;
          </button>
        </div>

        <!-- Floating badges -->
        <div class="absolute -top-4 -right-4 bg-white rounded-xl px-3 py-2 flex items-center gap-2"
             style="box-shadow:0 4px 16px rgba(0,0,0,.12)">
          <i data-lucide="shield-check" class="w-4 h-4 text-green-500"></i>
          <span class="text-xs font-semibold text-gray-700">Verified Badge</span>
        </div>
        <div class="absolute -bottom-4 -left-4 bg-white rounded-xl px-3 py-2 flex items-center gap-2"
             style="box-shadow:0 4px 16px rgba(0,0,0,.12)">
          <i data-lucide="star" class="w-4 h-4 text-yellow-400"></i>
          <span class="text-xs font-semibold text-gray-700">4.9 Rating</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Wave divider -->
  <div class="absolute bottom-0 left-0 right-0">
    <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"
         style="width:100%;height:64px;display:block">
      <path d="M0 80L60 66.7C120 53.3 240 26.7 360 20C480 13.3 600 26.7 720 33.3C840 40 960 40 1080 36.7C1200 33.3 1320 26.7 1380 23.3L1440 20V80H0Z" fill="white"/>
    </svg>
  </div>
</section>


<!-- ================================================================
     STATS BANNER
================================================================ -->
<section class="bg-white py-14 border-b border-gray-100">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
      <div class="fade-in">
        <p class="text-4xl font-bold mb-1" style="font-family:Sora,sans-serif;color:#3366ff">11+</p>
        <p class="text-sm text-gray-500 font-medium">Platform Modules</p>
      </div>
      <div class="fade-in" style="transition-delay:.1s">
        <p class="text-4xl font-bold mb-1" style="font-family:Sora,sans-serif;color:#3366ff">24/7</p>
        <p class="text-sm text-gray-500 font-medium">Access Anytime</p>
      </div>
      <div class="fade-in" style="transition-delay:.2s">
        <p class="text-4xl font-bold mb-1" style="font-family:Sora,sans-serif;color:#3366ff">P2P</p>
        <p class="text-sm text-gray-500 font-medium">Verified Learning</p>
      </div>
      <div class="fade-in" style="transition-delay:.3s">
        <p class="text-4xl font-bold mb-1" style="font-family:Sora,sans-serif;color:#3366ff">Agile</p>
        <p class="text-sm text-gray-500 font-medium">Development Model</p>
      </div>
    </div>
  </div>
</section>


<!-- ================================================================
     FEATURES
================================================================ -->
<section id="features" class="py-24 bg-slate-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center max-w-2xl mx-auto mb-16 fade-in">
      <span class="inline-block text-xs font-bold text-blue-600 uppercase tracking-widest mb-3 bg-blue-50 px-4 py-1.5 rounded-full">Core Features</span>
      <h2 class="text-4xl font-bold text-gray-900 mb-4" style="font-family:Sora,sans-serif">Everything You Need to Exchange Skills</h2>
      <p class="text-gray-500 text-lg leading-relaxed">A secure, feature-rich platform connecting students as both teachers and learners &mdash; with full transparency and trust built in.</p>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-7">

      <div class="feature-card bg-white rounded-2xl p-7 fade-in" style="box-shadow:0 4px 24px rgba(51,102,255,.08)">
        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-5">
          <i data-lucide="shield-check" class="w-6 h-6 text-blue-500"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2" style="font-family:Sora,sans-serif">Certificate Verification</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Students upload certificates reviewed by the administrator before teaching, ensuring authenticity and trust on the platform.</p>
      </div>

      <div class="feature-card bg-white rounded-2xl p-7 fade-in" style="box-shadow:0 4px 24px rgba(51,102,255,.08);transition-delay:.1s">
        <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mb-5">
          <i data-lucide="git-merge" class="w-6 h-6 text-purple-500"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2" style="font-family:Sora,sans-serif">Skill-Based Matchmaking</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Our intelligent system recommends suitable learning partners by matching skills you can teach with what others want to learn.</p>
      </div>

      <div class="feature-card bg-white rounded-2xl p-7 fade-in" style="box-shadow:0 4px 24px rgba(51,102,255,.08);transition-delay:.2s">
        <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center mb-5">
          <i data-lucide="users" class="w-6 h-6 text-emerald-500"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2" style="font-family:Sora,sans-serif">Peer-to-Peer Learning</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Every student can be both a teacher and a learner &mdash; swapping knowledge freely without any financial transaction involved.</p>
      </div>

      <div class="feature-card bg-white rounded-2xl p-7 fade-in" style="box-shadow:0 4px 24px rgba(51,102,255,.08);transition-delay:.05s">
        <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center mb-5">
          <i data-lucide="star" class="w-6 h-6 text-amber-500"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2" style="font-family:Sora,sans-serif">Ratings &amp; Reviews</h3>
        <p class="text-gray-500 text-sm leading-relaxed">After every session students rate and review partners, maintaining a trusted and quality-driven community.</p>
      </div>

      <div class="feature-card bg-white rounded-2xl p-7 fade-in" style="box-shadow:0 4px 24px rgba(51,102,255,.08);transition-delay:.15s">
        <div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center mb-5">
          <i data-lucide="calendar" class="w-6 h-6 text-rose-500"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2" style="font-family:Sora,sans-serif">Session Scheduling</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Easily schedule skill exchange sessions with matched partners, keeping the process organised and hassle-free.</p>
      </div>

      <div class="feature-card bg-white rounded-2xl p-7 fade-in" style="box-shadow:0 4px 24px rgba(51,102,255,.08);transition-delay:.25s">
        <div class="w-12 h-12 bg-cyan-50 rounded-xl flex items-center justify-center mb-5">
          <i data-lucide="bell" class="w-6 h-6 text-cyan-500"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2" style="font-family:Sora,sans-serif">Real-Time Notifications</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Stay updated with instant notifications for exchange requests, session reminders, and admin verifications.</p>
      </div>
    </div>
  </div>
</section>


<!-- ================================================================
     HOW IT WORKS
================================================================ -->
<section id="how-it-works" class="py-24 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center max-w-2xl mx-auto mb-16 fade-in">
      <span class="inline-block text-xs font-bold uppercase tracking-widest mb-3 bg-amber-50 px-4 py-1.5 rounded-full"
            style="color:#f97316">Simple Process</span>
      <h2 class="text-4xl font-bold text-gray-900 mb-4" style="font-family:Sora,sans-serif">How SkillSwap Works</h2>
      <p class="text-gray-500 text-lg">Get started in minutes and begin exchanging skills with verified students.</p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">

      <div class="relative text-center fade-in">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5" style="background:#3366ff">
          <i data-lucide="user-plus" class="w-7 h-7 text-white"></i>
        </div>
        <span class="absolute top-0 right-6 w-6 h-6 bg-blue-100 text-blue-600 text-xs font-bold rounded-full flex items-center justify-center">1</span>
        <h3 class="text-base font-semibold text-gray-900 mb-2" style="font-family:Sora,sans-serif">Create Your Profile</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Register securely and set up your profile with skills you can teach and want to learn.</p>
      </div>

      <div class="relative text-center fade-in" style="transition-delay:.15s">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5" style="background:#7c3aed">
          <i data-lucide="file-check" class="w-7 h-7 text-white"></i>
        </div>
        <span class="absolute top-0 right-6 w-6 h-6 bg-purple-100 text-purple-600 text-xs font-bold rounded-full flex items-center justify-center">2</span>
        <h3 class="text-base font-semibold text-gray-900 mb-2" style="font-family:Sora,sans-serif">Upload Certificates</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Submit supporting documents. Admin verifies them and grants you a trusted teaching badge.</p>
      </div>

      <div class="relative text-center fade-in" style="transition-delay:.3s">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5" style="background:#059669">
          <i data-lucide="search" class="w-7 h-7 text-white"></i>
        </div>
        <span class="absolute top-0 right-6 w-6 h-6 bg-emerald-100 text-emerald-600 text-xs font-bold rounded-full flex items-center justify-center">3</span>
        <h3 class="text-base font-semibold text-gray-900 mb-2" style="font-family:Sora,sans-serif">Find a Match</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Our matchmaking system recommends ideal partners whose skills align with yours.</p>
      </div>

      <div class="relative text-center fade-in" style="transition-delay:.45s">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5" style="background:#f97316">
          <i data-lucide="repeat-2" class="w-7 h-7 text-white"></i>
        </div>
        <span class="absolute top-0 right-6 w-6 h-6 bg-amber-100 text-amber-600 text-xs font-bold rounded-full flex items-center justify-center">4</span>
        <h3 class="text-base font-semibold text-gray-900 mb-2" style="font-family:Sora,sans-serif">Start Swapping</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Send an exchange request, schedule your session, and learn from each other for free!</p>
      </div>
    </div>
  </div>
</section>


<!-- ================================================================
     MODULES
================================================================ -->
<section id="modules" class="py-24" style="background:#131d5c;color:white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-16 items-center">

      <!-- Left copy -->
      <div class="fade-in">
        <span class="inline-block text-xs font-bold uppercase tracking-widest mb-3 px-4 py-1.5 rounded-full"
              style="color:#93c5fd;background:rgba(30,64,175,.5)">Platform Architecture</span>
        <h2 class="text-4xl font-bold mb-5" style="font-family:Sora,sans-serif">11 Powerful Modules Working Together</h2>
        <p class="text-lg leading-relaxed mb-8" style="color:#bfdbfe">
          SkillSwap is built on a modular PHP &amp; MySQL architecture, ensuring each feature is
          robust, independently testable, and ready for future enhancements.
        </p>
        <div class="flex flex-wrap gap-3">
          <span class="text-xs font-medium px-3 py-1.5 rounded-full"
                style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.1);color:#bfdbfe">HTML5 &amp; CSS3</span>
          <span class="text-xs font-medium px-3 py-1.5 rounded-full"
                style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.1);color:#bfdbfe">Bootstrap 5</span>
          <span class="text-xs font-medium px-3 py-1.5 rounded-full"
                style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.1);color:#bfdbfe">JavaScript</span>
          <span class="text-xs font-medium px-3 py-1.5 rounded-full"
                style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.1);color:#bfdbfe">PHP 8.x</span>
          <span class="text-xs font-medium px-3 py-1.5 rounded-full"
                style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.1);color:#bfdbfe">MySQL 8.x</span>
          <span class="text-xs font-medium px-3 py-1.5 rounded-full"
                style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.1);color:#bfdbfe">XAMPP</span>
          <span class="text-xs font-medium px-3 py-1.5 rounded-full"
                style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.1);color:#bfdbfe">Agile Methodology</span>
        </div>
      </div>

      <!-- Module grid -->
      <div class="grid sm:grid-cols-2 gap-3 fade-in" style="transition-delay:.15s">

        <div class="flex items-start gap-3 rounded-xl p-4"
             style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
          <i data-lucide="lock" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#93c5fd"></i>
          <div><p class="text-sm font-semibold text-white">Registration &amp; Auth</p><p class="text-xs mt-0.5" style="color:#93c5fd">Secure login system</p></div>
        </div>

        <div class="flex items-start gap-3 rounded-xl p-4"
             style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
          <i data-lucide="user-cog" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#c4b5fd"></i>
          <div><p class="text-sm font-semibold text-white">Profile Management</p><p class="text-xs mt-0.5" style="color:#93c5fd">Rich student profiles</p></div>
        </div>

        <div class="flex items-start gap-3 rounded-xl p-4"
             style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
          <i data-lucide="layers" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#6ee7b7"></i>
          <div><p class="text-sm font-semibold text-white">Skill Management</p><p class="text-xs mt-0.5" style="color:#93c5fd">Teach &amp; learn skills</p></div>
        </div>

        <div class="flex items-start gap-3 rounded-xl p-4"
             style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
          <i data-lucide="file-check-2" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#fcd34d"></i>
          <div><p class="text-sm font-semibold text-white">Certificate Verification</p><p class="text-xs mt-0.5" style="color:#93c5fd">Document upload &amp; review</p></div>
        </div>

        <div class="flex items-start gap-3 rounded-xl p-4"
             style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
          <i data-lucide="shield" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#fca5a5"></i>
          <div><p class="text-sm font-semibold text-white">Admin Panel</p><p class="text-xs mt-0.5" style="color:#93c5fd">Platform management</p></div>
        </div>

        <div class="flex items-start gap-3 rounded-xl p-4"
             style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
          <i data-lucide="git-merge" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#67e8f9"></i>
          <div><p class="text-sm font-semibold text-white">Matchmaking</p><p class="text-xs mt-0.5" style="color:#93c5fd">Smart partner pairing</p></div>
        </div>

        <div class="flex items-start gap-3 rounded-xl p-4"
             style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
          <i data-lucide="send" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#a5b4fc"></i>
          <div><p class="text-sm font-semibold text-white">Exchange Requests</p><p class="text-xs mt-0.5" style="color:#93c5fd">Send, accept, reject</p></div>
        </div>

        <div class="flex items-start gap-3 rounded-xl p-4"
             style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
          <i data-lucide="calendar-check" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#5eead4"></i>
          <div><p class="text-sm font-semibold text-white">Session Scheduling</p><p class="text-xs mt-0.5" style="color:#93c5fd">Organised sessions</p></div>
        </div>

        <div class="flex items-start gap-3 rounded-xl p-4"
             style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
          <i data-lucide="star" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#fde68a"></i>
          <div><p class="text-sm font-semibold text-white">Ratings &amp; Reviews</p><p class="text-xs mt-0.5" style="color:#93c5fd">Quality feedback loop</p></div>
        </div>

        <div class="flex items-start gap-3 rounded-xl p-4"
             style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
          <i data-lucide="bell" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#fdba74"></i>
          <div><p class="text-sm font-semibold text-white">Notifications</p><p class="text-xs mt-0.5" style="color:#93c5fd">Real-time alerts</p></div>
        </div>

        <div class="flex items-start gap-3 rounded-xl p-4 sm:col-span-2"
             style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
          <i data-lucide="bar-chart-2" class="w-5 h-5 mt-0.5 flex-shrink-0" style="color:#f9a8d4"></i>
          <div><p class="text-sm font-semibold text-white">Reports &amp; Analytics</p><p class="text-xs mt-0.5" style="color:#93c5fd">Platform insights &amp; monitoring</p></div>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ================================================================
     ABOUT / MISSION
================================================================ -->
<section id="about" class="py-24 bg-slate-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center max-w-2xl mx-auto mb-16 fade-in">
      <span class="inline-block text-xs font-bold text-emerald-600 uppercase tracking-widest mb-3 bg-emerald-50 px-4 py-1.5 rounded-full">About the Platform</span>
      <h2 class="text-4xl font-bold text-gray-900 mb-4" style="font-family:Sora,sans-serif">Our Mission &amp; Objectives</h2>
      <p class="text-gray-500 text-lg">SkillSwap addresses the limitations of traditional one-way learning platforms by creating a verified, collaborative, and cost-free skill exchange environment.</p>
    </div>

    <div class="grid md:grid-cols-2 gap-8 mb-10">

      <!-- Problem -->
      <div class="rounded-2xl p-8 fade-in" style="background:#fff1f2;border:1px solid #fecdd3">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#ffe4e6">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-500"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-900" style="font-family:Sora,sans-serif">The Problem</h3>
        </div>
        <ul class="space-y-3">
          <li class="flex items-start gap-3 text-sm text-gray-600">
            <i data-lucide="x-circle" class="w-4 h-4 text-rose-400 mt-0.5 flex-shrink-0"></i>
            Existing platforms require paid courses or subscriptions
          </li>
          <li class="flex items-start gap-3 text-sm text-gray-600">
            <i data-lucide="x-circle" class="w-4 h-4 text-rose-400 mt-0.5 flex-shrink-0"></i>
            One-way learning &mdash; no mutual knowledge exchange
          </li>
          <li class="flex items-start gap-3 text-sm text-gray-600">
            <i data-lucide="x-circle" class="w-4 h-4 text-rose-400 mt-0.5 flex-shrink-0"></i>
            No proper verification of teaching credentials
          </li>
          <li class="flex items-start gap-3 text-sm text-gray-600">
            <i data-lucide="x-circle" class="w-4 h-4 text-rose-400 mt-0.5 flex-shrink-0"></i>
            Manual, time-consuming search for learning partners
          </li>
          <li class="flex items-start gap-3 text-sm text-gray-600">
            <i data-lucide="x-circle" class="w-4 h-4 text-rose-400 mt-0.5 flex-shrink-0"></i>
            Lack of intelligent recommendation systems
          </li>
        </ul>
      </div>

      <!-- Solution -->
      <div class="rounded-2xl p-8 fade-in" style="background:#f0fdf4;border:1px solid #bbf7d0;transition-delay:.15s">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#dcfce7">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-500"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-900" style="font-family:Sora,sans-serif">Our Solution</h3>
        </div>
        <ul class="space-y-3">
          <li class="flex items-start gap-3 text-sm text-gray-600">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0"></i>
            Skill-for-skill exchange &mdash; no monetary transactions
          </li>
          <li class="flex items-start gap-3 text-sm text-gray-600">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0"></i>
            Bidirectional peer-to-peer learning model
          </li>
          <li class="flex items-start gap-3 text-sm text-gray-600">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0"></i>
            Admin-verified certificates and trusted badges
          </li>
          <li class="flex items-start gap-3 text-sm text-gray-600">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0"></i>
            Automated skill-based matchmaking system
          </li>
          <li class="flex items-start gap-3 text-sm text-gray-600">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0"></i>
            Structured sessions with ratings and reviews
          </li>
        </ul>
      </div>
    </div>

    <!-- Future roadmap -->
    <div class="rounded-2xl p-8 fade-in" style="background:#eff6ff;border:1px solid #bfdbfe">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#dbeafe">
          <i data-lucide="rocket" class="w-5 h-5 text-blue-500"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900" style="font-family:Sora,sans-serif">Future Enhancements</h3>
      </div>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <i data-lucide="cpu" class="w-4 h-4 text-blue-400"></i> AI-Based Skill Recommendations
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <i data-lucide="message-circle" class="w-4 h-4 text-blue-400"></i> Real-Time Chat
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <i data-lucide="video" class="w-4 h-4 text-blue-400"></i> Video Conferencing
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <i data-lucide="mail" class="w-4 h-4 text-blue-400"></i> Email Notifications
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <i data-lucide="smartphone" class="w-4 h-4 text-blue-400"></i> Mobile App Support
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <i data-lucide="award" class="w-4 h-4 text-blue-400"></i> Digital Certificates
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ================================================================
     TESTIMONIALS
================================================================ -->
<section class="py-24 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center max-w-xl mx-auto mb-14 fade-in">
      <span class="inline-block text-xs font-bold text-amber-600 uppercase tracking-widest mb-3 bg-amber-50 px-4 py-1.5 rounded-full">Community Voices</span>
      <h2 class="text-4xl font-bold text-gray-900" style="font-family:Sora,sans-serif">What Students Are Saying</h2>
    </div>

    <div class="grid md:grid-cols-3 gap-7">

      <div class="bg-slate-50 border border-gray-100 rounded-2xl p-7 fade-in">
        <div class="flex gap-1 mb-4">
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
        </div>
        <p class="text-gray-600 text-sm leading-relaxed mb-5">
          &ldquo;I taught Python to two students and learned Graphic Design in return. SkillSwap completely changed the way I think about learning &mdash; no money needed, just knowledge!&rdquo;
        </p>
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-white text-sm" style="background:#3366ff">A</div>
          <div>
            <p class="text-sm font-semibold text-gray-800">Arjun Sharma</p>
            <p class="text-xs text-gray-400">Computer Science, 3rd Year</p>
          </div>
        </div>
      </div>

      <div class="bg-slate-50 border border-gray-100 rounded-2xl p-7 fade-in" style="transition-delay:.1s">
        <div class="flex gap-1 mb-4">
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
        </div>
        <p class="text-gray-600 text-sm leading-relaxed mb-5">
          &ldquo;The certificate verification gave me confidence that skills offered are genuine. I found an amazing partner to learn UI/UX design within minutes of signing up!&rdquo;
        </p>
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-white text-sm" style="background:#7c3aed">P</div>
          <div>
            <p class="text-sm font-semibold text-gray-800">Priya Nair</p>
            <p class="text-xs text-gray-400">Design Student, 2nd Year</p>
          </div>
        </div>
      </div>

      <div class="bg-slate-50 border border-gray-100 rounded-2xl p-7 fade-in" style="transition-delay:.2s">
        <div class="flex gap-1 mb-4">
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
        </div>
        <p class="text-gray-600 text-sm leading-relaxed mb-5">
          &ldquo;The matchmaking system is incredibly accurate. It paired me with students who perfectly complemented my goals. Session scheduling made everything super easy.&rdquo;
        </p>
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-white text-sm" style="background:#059669">R</div>
          <div>
            <p class="text-sm font-semibold text-gray-800">Rahul Verma</p>
            <p class="text-xs text-gray-400">MBA Student, 1st Year</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ================================================================
     CTA BANNER
================================================================ -->
<section class="py-20 hero-gradient relative overflow-hidden">
  <div class="blob" style="width:256px;height:256px;background:#93c5fd;top:-40px;right:-40px"></div>
  <div class="blob" style="width:192px;height:192px;background:#818cf8;bottom:20px;left:40px;animation-delay:2s"></div>

  <div class="relative max-w-4xl mx-auto px-4 text-center">
    <h2 class="text-4xl sm:text-5xl font-bold text-white mb-5 fade-in" style="font-family:Sora,sans-serif">
      Ready to Start Exchanging Skills?
    </h2>
    <p class="text-lg mb-10 fade-in" style="color:#bfdbfe;transition-delay:.1s">
      Join a growing community of students who learn by teaching. No fees. No barriers. Just knowledge.
    </p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center fade-in" style="transition-delay:.2s">
      <a href="/auth/register.php"
         class="inline-flex items-center justify-center gap-2 text-white font-semibold text-base px-9 py-4 rounded-xl transition-all hover:scale-105"
         style="background:#f97316">
        <i data-lucide="zap" class="w-5 h-5"></i> Join SkillSwap Today
      </a>
      <a href="#features"
         class="inline-flex items-center justify-center gap-2 text-white font-semibold text-base px-9 py-4 rounded-xl transition-all"
         style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3)">
        <i data-lucide="info" class="w-5 h-5"></i> Learn More
      </a>
    </div>
  </div>
</section>


<!-- ================================================================
     FOOTER
================================================================ -->
<footer style="background:#0f172a;color:white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">

      <!-- Brand -->
      <div class="sm:col-span-2 lg:col-span-1">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#3366ff">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M8 3H5a2 2 0 0 0-2 2v3"/>
              <path d="M21 8V5a2 2 0 0 0-2-2h-3"/>
              <path d="M3 16v3a2 2 0 0 0 2 2h3"/>
              <path d="M16 21h3a2 2 0 0 0 2-2v-3"/>
              <path d="m7 16 3-3 2 2 3-4 2 5"/>
            </svg>
          </div>
          <span class="text-xl font-bold" style="font-family:Sora,sans-serif">
            Skill<span style="color:#f97316">Swap</span>
          </span>
        </div>
        <p class="text-sm leading-relaxed mb-4" style="color:#94a3b8">
          A student skill exchange platform promoting peer-to-peer learning through verified,
          free, and collaborative knowledge sharing.
        </p>
        <p class="text-xs" style="color:#64748b">Built with PHP &amp; MySQL &bull; Agile Development</p>
      </div>

      <!-- Platform links -->
      <div>
        <h4 class="text-sm font-semibold text-white mb-4">Platform</h4>
        <ul class="space-y-2">
          <li><a href="#features"     class="text-sm hover:text-white transition-colors" style="color:#94a3b8">Features</a></li>
          <li><a href="#how-it-works" class="text-sm hover:text-white transition-colors" style="color:#94a3b8">How It Works</a></li>
          <li><a href="#modules"      class="text-sm hover:text-white transition-colors" style="color:#94a3b8">Modules</a></li>
          <li><a href="#about"        class="text-sm hover:text-white transition-colors" style="color:#94a3b8">About</a></li>
        </ul>
      </div>

      <!-- User links -->
      <div>
        <h4 class="text-sm font-semibold text-white mb-4">For Users</h4>
        <ul class="space-y-2">
          <li><a href="/auth/register.php" class="text-sm hover:text-white transition-colors" style="color:#94a3b8">Sign Up</a></li>
          <li><a href="/auth/login.php" class="text-sm hover:text-white transition-colors" style="color:#94a3b8">Sign In</a></li>
          <li><a href="#features" class="text-sm hover:text-white transition-colors" style="color:#94a3b8">Get Verified</a></li>
          <li><a href="#how-it-works" class="text-sm hover:text-white transition-colors" style="color:#94a3b8">Find a Partner</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div>
        <h4 class="text-sm font-semibold text-white mb-4">Contact</h4>
        <ul class="space-y-2">
          <li class="flex items-center gap-2 text-sm" style="color:#94a3b8">
            <i data-lucide="mail" class="w-3.5 h-3.5"></i> support@skillswap.edu
          </li>
          <li class="flex items-center gap-2 text-sm" style="color:#94a3b8">
            <i data-lucide="globe" class="w-3.5 h-3.5"></i> www.skillswap.edu
          </li>
        </ul>
      </div>
    </div>

    <div class="pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs"
         style="border-top:1px solid rgba(255,255,255,.1);color:#475569">
      <p>&copy; 2026 SkillSwap &ndash; Student Skill Exchange Platform. All rights reserved.</p>
      <p>Developed using PHP &bull; MySQL &bull; Bootstrap &bull; JavaScript</p>
    </div>
  </div>
</footer>


<!-- ================================================================
     JAVASCRIPT
================================================================ -->
<script>
  // Init icons
  lucide.createIcons();

  // Navbar scroll shadow
  const navbar = document.getElementById("navbar");
  window.addEventListener("scroll", () => {
    navbar.classList.toggle("scrolled", window.scrollY > 20);
  });

  // Mobile menu
  const menuBtn     = document.getElementById("menu-btn");
  const mobileMenu  = document.getElementById("mobile-menu");
  menuBtn.addEventListener("click", () => mobileMenu.classList.toggle("hidden"));
  mobileMenu.querySelectorAll("a").forEach(a =>
    a.addEventListener("click", () => mobileMenu.classList.add("hidden"))
  );

  // Scroll fade-in
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add("visible"); io.unobserve(e.target); }
    });
  }, { threshold: 0.12 });
  document.querySelectorAll(".fade-in").forEach(el => io.observe(el));

</script>

</body>
</html>
