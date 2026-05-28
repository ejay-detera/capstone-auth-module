<template>
  <div class="lp-wrap">
    <nav class="nav">
      <div class="nav-inner">
        <div class="nav-logo">
          <img src="/images/SBSI-logo.png" alt="SBSI" />
        </div>

        <div class="nav-ctas">
          <router-link to="/login" class="btn-login">
            Log in
          </router-link>
        </div>
      </div>
    </nav>

    <section class="hero">
      <div class="hero-bg"></div>
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <div class="hero-badge">
          <span class="badge-dot"></span>
          ISO 9001:2015 Certified
        </div>
        <h1>25 Years of Innovating Diagnostics Solutions</h1>
        <div class="hero-divider"></div>
        <p>
          A unified platform for managing contracts, expenses,
          productivity, and support — built for the business finance
          and marketing industry.
        </p>

        <div class="hero-ctas">
          <router-link to="/login" class="cta-primary">
            Get started
          </router-link>
        </div>
      </div>
    </section>

    <section class="features-glass">
      <div class="mesh-gradient bg-purple"></div>
      <div class="mesh-gradient bg-blue"></div>

      <div class="section-wrap">
        <div class="section-eyebrow">Features</div>

        <h2 class="section-heading">
          Everything your team needs
        </h2>

        <p class="section-sub">
          Four integrated systems designed to streamline your
          operations from end to end.
        </p>

        <div class="feat-grid">
          <div 
            class="feat-card reveal" 
            v-for="f in features" 
            :key="f.title"
            :style="{ '--card-blob': f.blobColor, '--bottom-border-color': f.bottomBorderColor }"
          >
            <div class="card-ambient-blob"></div>

            <div class="card-bottom-accent"></div>

            <div class="feat-icon-wrap" :style="{ background: f.iconBg, color: f.iconColor }">
              <span v-html="f.icon"></span>
            </div>
            
            <div class="feat-body">
              <h3>{{ f.title }}</h3>
              <p>{{ f.desc }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <footer>
      <div class="footer-inner">
        <img
          src="/images/SBSI-logo.png"
          alt="SBSI Logo"
        />

        <span class="footer-copy">
          © 2026 Scientific Biotech Specialties, Inc. All rights reserved.
        </span>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';

const features = [
  {
    title: 'Contract Management',
    desc: 'Track, manage, and renew contracts with full audit trails and approval workflows.',
    iconBg: '#eff6ff',
    iconColor: '#2e85d8',
    bottomBorderColor: '#2e85d8',
    blobColor: 'radial-gradient(circle, rgba(59,130,246,0.18) 0%, transparent 65%)',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>`
  },
  {
    title: 'Smart Expense Reimbursement',
    desc: 'Smart expense submissions with real-time status tracking and automated approvals.',
    iconBg: '#f0fdf4',
    iconColor: '#22c55e',
    bottomBorderColor: '#22c55e',
    blobColor: 'radial-gradient(circle, rgba(34,197,94,0.18) 0%, transparent 65%)',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>`
  },
  {
    title: 'Productivity Reports',
    desc: 'Generate detailed productivity analytics and performance summaries across teams.',
    iconBg: '#faf5ff',
    iconColor: '#a855f7',
    bottomBorderColor: '#a855f7',
    blobColor: 'radial-gradient(circle, rgba(168,85,247,0.18) 0%, transparent 65%)',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>`
  },
  {
    title: 'Ticketing System',
    desc: 'Submit, escalate, and resolve support tickets with SLA breach monitoring built in.',
    iconBg: '#fffbeb',
    iconColor: '#d97706',
    bottomBorderColor: '#f59e0b',
    blobColor: 'radial-gradient(circle, rgba(245,158,11,0.18) 0%, transparent 65%)',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>`
  }
];

onMounted(() => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('active');
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));
});
</script>

<style scoped>
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

.lp-wrap {
  font-family: 'Poppins', 'Montserrat', sans-serif;
  background: #07071a;
  color: #fff;
  overflow-x: hidden;
}

/* NAV */

.nav {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 100;
  background: rgba(14, 14, 46, 0.7);
  backdrop-filter: blur(16px);
  border-bottom: 0.5px solid rgba(255, 255, 255, 0.08);
}

.nav-inner {
  max-width: 1300px;
  height: 64px;
  margin: 0 auto;
  padding: 0 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.nav-logo img {
  height: 60px;
  object-fit: contain;
}

.nav-ctas {
  display: flex;
  gap: 10px;
}

.btn-login {
  text-decoration: none;
  border-radius: 8px;
  padding: 9px 22px;
  background: #2e85d8;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
}

/* HERO */

.hero {
  position: relative;
  width: 100%;
  min-height: 100vh;
  display: flex;
  align-items: center;
  overflow: hidden;
}

.hero-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  background-image: url('/images/SBSI-bg.png');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to right,
    rgba(14, 14, 46, 0.90) 0%,
    rgba(14, 14, 46, 0.60) 30%,
    rgba(14, 14, 46, 0.60) 70%,
    rgba(14, 14, 46, 0.90) 100%
  );
}

.hero-content {
  position: relative;
  z-index: 2;
  max-width: 1440px;
  margin: 0 auto;
  padding: 100px 40px 60px;
  display: flex;
  flex-direction: column;
  align-items: center;    
  text-align: center;     
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 28px;
  padding: 6px 16px;
  background: rgba(255, 255, 255, 0.08);
  border: 0.5px solid rgba(255, 255, 255, 0.18);
  border-radius: 20px;
  color: rgba(255, 255, 255, 0.8);
  font-size: 12px;
}

.badge-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #4ade80;
}

.hero h1 {
  max-width: 900px; 
  margin-left: auto;
  margin-right: auto;
  font-size: clamp(38px, 6vw, 72px);
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -0.001em;
}

.hero-divider {
  width: 250px;
  height: 4px;
  margin: 24px 0;
  background: #2e85d8;
  border-radius: 2px;
}

.hero p {
  max-width: 550px;
  margin-bottom: 40px;
  color: rgba(255, 255, 255, 0.65);
  font-size: 15px;
  line-height: 1.8;
}

.hero-ctas {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}

.cta-primary {
  display: inline-flex;
  align-items: center;
  padding: 16px 48px;
  font-size: 16px;
  background: #2e85d8;
  color: #fff;
  font-weight: 600;
  border-radius: 10px;
  text-decoration: none;
}

/* GLASSMORPHIC FEATURES SECTION */

.features-glass {
  position: relative;
  padding: 70px 32px; 
  background: #eef2f7; 
  overflow: hidden;
  z-index: 1;
}

/* Mesh Backdrop Gradients */
.mesh-gradient {
  position: absolute;
  border-radius: 50%;
  filter: blur(120px);
  opacity: 0.65;
  z-index: -1;
  pointer-events: none;
}
.bg-purple {
  top: -10%;
  left: 20%;
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(235,217,255,1) 0%, transparent 70%);
}
.bg-blue {
  bottom: -10%;
  right: 5%;
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(212,234,255,1) 0%, transparent 70%);
}

.section-wrap {
  max-width: 1440px;
  margin: 0 auto;
  padding: 0 40px;
}

.section-eyebrow {
  margin-bottom: 14px;
  color: #6366f1; 
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 2.5px;
  text-transform: uppercase;
}

.section-heading {
  margin-bottom: 16px;
  font-size: clamp(28px, 3.5vw, 42px);
  font-weight: 800;
  color: #1e1b4b; 
  letter-spacing: -0.02em;
}

.section-sub {
  max-width: 600px;
  color: #52525b;
  font-size: 16px;
  line-height: 1.6;
}

/* IMPROVED VIEWPORT GRID GRID */
.feat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 32px;
  margin-top: 36px; 
  padding: 40px 10px;
}

/* Glass Card Architecture */
.feat-card {
  position: relative;
  overflow: hidden;
  padding: 40px 36px 44px; 
  border-radius: 32px; 
  background: rgba(255, 255, 255, 0.45);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  
  border: 1.5px solid rgba(226, 232, 240, 0.8); 
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.04);
  
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 28px; 
  
  transform-style: preserve-3d;
  backface-visibility: hidden;
  will-change: transform, box-shadow, opacity;
  
  transition: opacity 0.8s cubic-bezier(0.34, 1.56, 0.64, 1),
              transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1),
              box-shadow 0.4s cubic-bezier(0.25, 1, 0.5, 1),
              border-color 0.3s ease;
  z-index: 10;
}

.feat-card:hover {
  transform: translateY(-20px) scale(1.12) !important; 
  border-color: #ffffff; 
  z-index: 999 !important; 
  
  box-shadow: 0 50px 100px -20px rgba(100, 116, 139, 0.22),
              0 20px 40px -30px rgba(0, 0, 0, 0.15),
              0 0 0 2px rgba(255, 255, 255, 1) !important;
}

/* Bottom Color Border Accent Strip Line */
.card-bottom-accent {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 6px; 
  background: var(--bottom-border-color);
  z-index: 3;
}

/* Pop Scroll Entrance Definition */
.reveal {
  opacity: 0;
  transform: translateY(60px) scale(0.85); 
}

.reveal.active {
  opacity: 1;
  transform: translateY(0) scale(1);
}

/* Animated Card Inner Background Blur Blob */
.card-ambient-blob {
  position: absolute;
  top: -20%;
  right: -20%;
  width: 70%;
  height: 70%;
  background: var(--card-blob);
  filter: blur(30px);
  border-radius: 50%;
  z-index: 0;
  pointer-events: none;
  transition: transform 0.6s cubic-bezier(0.2, 1, 0.3, 1);
}

.feat-card:hover .card-ambient-blob {
  transform: scale(2) translate(-20%, 20%);
}

.feat-icon-wrap, 
.feat-body {
  position: relative;
  z-index: 2; 
}

.feat-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 16px; 
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: inset 0 -2px 4px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.02);
}

.feat-body {
  display: flex;
  flex-direction: column;
  gap: 12px; 
  text-align: left;
}

.feat-card h3 {
  font-size: 22px;
  font-weight: 700;
  color: #1e1b4b; 
  letter-spacing: -0.01em;
  line-height: 1.3;
}

.feat-card p {
  color: #4b5563;
  font-size: 15px;
  line-height: 1.6;
}

/* FOOTER */

footer {
  padding: 32px 40px;
  background: #07071a;
  border-top: 1.5px solid rgba(255, 255, 255, 0.05);
}

.footer-inner {
  max-width: 1300px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.footer-inner img {
  height: 32px; 
  object-fit: contain;
  opacity: 0.65;
}

.footer-copy {
  color: rgba(255, 255, 255, 0.35);
  font-size: 13px;
  font-weight: 400;
}

/* RESPONSIVE LAYOUT CONFIGURATIONS */

@media (max-width: 1200px) {
  .feat-grid {
    grid-template-columns: repeat(2, 1fr); 
    gap: 28px;
  }
}

@media (max-width: 768px) {
  .nav-inner {
    padding: 0 16px;
  }

  .nav-logo img {
    height: 45px; 
  }

  .hero-content {
    padding: 120px 16px 48px;
  }

  .features-glass {
    padding: 60px 16px; 
  }
  
  .section-wrap {
    padding: 0 8px;
  }

  .feat-grid {
    grid-template-columns: repeat(2, 1fr); 
    gap: 16px;
    padding: 20px 5px;
  }

  .feat-card {
    padding: 24px 20px;
    border-radius: 20px;
    gap: 16px;
  }

  .feat-card h3 {
    font-size: 18px;
  }

  .feat-card p {
    font-size: 13px;
    line-height: 1.5;
  }

  /* MOBILE FOOTER ADJUSTMENTS */
  footer {
    padding: 32px 16px;
  }

  .footer-inner {
    flex-direction: column; 
    align-items: flex-start;
    gap: 16px; 
    text-align: left;
  }

  .footer-inner img {
    height: 28px;
  }

  .footer-copy {
    font-size: 12px;
    line-height: 1.5;
  }
}

@media (max-width: 480px) {
  .feat-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  
  .feat-card {
    padding: 20px 14px;
    border-radius: 16px;
  }
  
  .feat-card h3 {
    font-size: 15px;
  }

  .feat-card p {
    font-size: 11px;
    line-height: 1.4;
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.hero-content > * {
  animation: fadeInUp 0.8s ease-out forwards;
}

.hero h1 { animation-delay: 0.2s; }
.hero-divider { animation-delay: 0.4s; }
.hero p { animation-delay: 0.6s; }
.hero-ctas { animation-delay: 0.8s; }

:global(html),
:global(body),
:global(#app) {
  margin: 0;
  padding: 0;
  width: 100%;
  min-height: 100%;
  overflow-x: hidden;
  background: #07071a;
}
</style>