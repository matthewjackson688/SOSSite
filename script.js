const yearEl = document.getElementById("year");
if (yearEl) {
  yearEl.textContent = new Date().getFullYear();
}

const helloBtn = document.getElementById("helloBtn");
if (helloBtn) {
  helloBtn.addEventListener("click", () => {
    alert("Hello from a local site!");
  });
}

const THEME_KEY = "sos-color-mode";
const THEMES = new Set([
  "default",
  "high-contrast",
  "protanopia-safe",
  "deuteranopia-safe",
  "tritanopia-safe",
]);

function applyTheme(theme) {
  if (!theme || theme === "default") {
    document.body.removeAttribute("data-theme");
    return;
  }
  document.body.setAttribute("data-theme", theme);
}

const savedTheme = localStorage.getItem(THEME_KEY);
if (THEMES.has(savedTheme)) {
  applyTheme(savedTheme);
}

const themeSelect = document.getElementById("themeSelect");
if (themeSelect) {
  const initialTheme = THEMES.has(savedTheme) ? savedTheme : "default";
  themeSelect.value = initialTheme;

  themeSelect.addEventListener("change", (event) => {
    const nextTheme = event.target.value;
    if (!THEMES.has(nextTheme)) {
      return;
    }

    applyTheme(nextTheme);
    localStorage.setItem(THEME_KEY, nextTheme);
  });
}
