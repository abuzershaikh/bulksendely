/* ========================================================================
   DelyntroBot AI Effects — Zero-Risk, Isolated
   ⚠️ JS-ONLY — No backend, PHP, route, or API changes.
   ======================================================================== */

/* ─────────────────────────────────────────────
   🌌 1. ANIMATED DARK-MODE PARTICLES
   ───────────────────────────────────────────── */
(() => {
  const canvas = document.getElementById("aiParticles");
  if (!canvas) return;
  const ctx = canvas.getContext("2d");

  let particles = [];
  const COUNT = 60;
  let animId = null;

  function resize() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  }
  window.addEventListener("resize", resize);
  resize();

  function create() {
    return {
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height,
      r: Math.random() * 2 + 0.5,
      vx: Math.random() * 0.3 - 0.15,
      vy: Math.random() * 0.3 - 0.15,
      o: Math.random() * 0.35 + 0.1
    };
  }

  particles = Array.from({ length: COUNT }, create);

  function isDark() {
    return document.documentElement.getAttribute("data-theme") === "dark"
      || document.body.classList.contains("dark");
  }

  function draw() {
    if (!isDark()) {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      // Keep checking in background at lower frequency
      setTimeout(() => { animId = requestAnimationFrame(draw); }, 500);
      return;
    }

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Draw connection lines between nearby particles
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 120) {
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.strokeStyle = `rgba(99,102,241,${0.06 * (1 - dist / 120)})`;
          ctx.lineWidth = 0.5;
          ctx.stroke();
        }
      }
    }

    // Draw particles
    particles.forEach(p => {
      p.x += p.vx;
      p.y += p.vy;

      if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
      if (p.y < 0 || p.y > canvas.height) p.vy *= -1;

      // Subtle pulsing opacity
      const pulse = Math.sin(Date.now() * 0.001 + p.x * 0.01) * 0.08;

      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(99,102,241,${p.o + pulse})`;
      ctx.fill();

      // Subtle glow
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r * 3, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(99,102,241,${(p.o + pulse) * 0.08})`;
      ctx.fill();
    });

    animId = requestAnimationFrame(draw);
  }

  draw();
})();


/* ─────────────────────────────────────────────
   🧠 2. AI ADAPTIVE COLORS (Usage Intelligence)
   ───────────────────────────────────────────── */
(() => {
  const key = "ui-activity-score";
  let score = Number(localStorage.getItem(key)) || 0;
  let moveThrottle = 0;

  // Track activity
  document.addEventListener("click", () => { score += 1; });
  document.addEventListener("keydown", () => { score += 0.5; });
  document.addEventListener("mousemove", () => {
    moveThrottle++;
    if (moveThrottle % 50 === 0) score += 0.02;
  });

  // Apply adaptive intensity every 5 seconds
  setInterval(() => {
    score = Math.min(score, 1000);
    localStorage.setItem(key, Math.round(score));
    applyAITheme(score);
  }, 5000);

  function applyAITheme(score) {
    // Only intensify in dark mode
    if (document.documentElement.getAttribute("data-theme") !== "dark") return;

    const intensity = Math.min(score / 600, 1);

    // Slightly boost primary vibrancy for power users
    const primaryLightness = 60 + intensity * 8; // 60% → 68%
    const primarySat = 85 + intensity * 10;      // 85% → 95%
    document.documentElement.style.setProperty(
      "--dl-primary-adaptive",
      `hsl(235, ${primarySat}%, ${primaryLightness}%)`
    );

    // Boost accent glow
    const glowOpacity = 0.15 + intensity * 0.20;
    document.documentElement.style.setProperty(
      "--dl-shadow-glow",
      `0 0 ${15 + intensity * 10}px rgba(99, 102, 241, ${glowOpacity})`
    );
  }

  // Initial apply
  applyAITheme(score);
})();


/* ─────────────────────────────────────────────
   🌓 3. AUTO THEME BY SYSTEM TIME (Smart Default)
   ───────────────────────────────────────────── */
(() => {
  // Skip if user has manually toggled the theme
  const userManual = localStorage.getItem("dl-theme");
  if (userManual) return; // Respect manual override

  const hour = new Date().getHours();
  const autoTheme = (hour >= 19 || hour < 7) ? "dark" : "light";

  document.documentElement.setAttribute("data-theme", autoTheme);

  // Apply body class when DOM is ready
  if (document.body) {
    applyAutoClass(autoTheme);
  } else {
    document.addEventListener("DOMContentLoaded", () => applyAutoClass(autoTheme));
  }

  function applyAutoClass(theme) {
    if (theme === "dark") {
      document.body.classList.add("dark", "dl-dark");
      document.body.classList.remove("dl-light");
    } else {
      document.body.classList.remove("dark", "dl-dark");
      document.body.classList.add("dl-light");
    }
  }
})();
