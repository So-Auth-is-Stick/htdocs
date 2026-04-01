const menuToggle = document.getElementById('menuToggle');
const mobileMenu = document.getElementById('mobileMenu');

if (menuToggle && mobileMenu) {
  menuToggle.addEventListener('click', () => {
    mobileMenu.classList.toggle('active');
  });

  mobileMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      mobileMenu.classList.remove('active');
    });
  });
}

const revealItems = document.querySelectorAll('.reveal');
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('active');
    }
  });
}, {
  threshold: 0.15
});

revealItems.forEach(item => observer.observe(item));

const repCounter = document.getElementById('repCounter');
if (repCounter) {
  let reps = 12;
  let direction = 1;

  setInterval(() => {
    reps += direction;

    if (reps >= 16) direction = -1;
    if (reps <= 12) direction = 1;

    repCounter.textContent = reps;
  }, 1200);
}

document.addEventListener('mousemove', (e) => {
  const chips = document.querySelectorAll('.floating-chip');
  const x = (e.clientX / window.innerWidth - 0.5) * 12;
  const y = (e.clientY / window.innerHeight - 0.5) * 12;

  chips.forEach((chip, index) => {
    const factor = (index + 1) * 0.35;
    chip.style.transform = `translate(${x * factor}px, ${y * factor}px)`;
  });
});
