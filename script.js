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

function createThemeControl(initialTheme) {
  const wrap = document.createElement("div");
  wrap.className = "theme-floater";

  const label = document.createElement("label");
  label.className = "sr-only";
  label.setAttribute("for", "themeSelect");
  label.textContent = "Color mode";

  const select = document.createElement("select");
  select.id = "themeSelect";
  select.setAttribute("aria-label", "Color mode");

  const options = [
    ["default", "Default"],
    ["high-contrast", "High Contrast"],
    ["protanopia-safe", "Protanopia-safe"],
    ["deuteranopia-safe", "Deuteranopia-safe"],
    ["tritanopia-safe", "Tritanopia-safe"],
  ];

  for (const [value, text] of options) {
    const option = document.createElement("option");
    option.value = value;
    option.textContent = text;
    select.appendChild(option);
  }

  select.value = initialTheme;
  select.addEventListener("change", (event) => {
    const nextTheme = event.target.value;
    if (!THEMES.has(nextTheme)) return;
    applyTheme(nextTheme);
    localStorage.setItem(THEME_KEY, nextTheme);
  });

  wrap.appendChild(label);
  wrap.appendChild(select);
  document.body.appendChild(wrap);
}

const savedTheme = localStorage.getItem(THEME_KEY);
const initialTheme = THEMES.has(savedTheme) ? savedTheme : "default";
applyTheme(initialTheme);
createThemeControl(initialTheme);


const welcomeTextEl = document.getElementById("welcomeText");
if (welcomeTextEl) {
  const greetings = [
    "Welcome to #SOS",
    "Croeso i #SOS",
    "Karibu kwenye #SOS",
    "Sanu da zuwa #SOS",
    "Kugamuchirwa ku #SOS",
  ];

  let greetingIndex = 0;
  setInterval(() => {
    welcomeTextEl.classList.add("welcome-text-swipe");

    setTimeout(() => {
      greetingIndex = (greetingIndex + 1) % greetings.length;
      welcomeTextEl.textContent = greetings[greetingIndex];
    }, 180);

    setTimeout(() => {
      welcomeTextEl.classList.remove("welcome-text-swipe");
    }, 360);
  }, 4000);
}
