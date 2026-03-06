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

  const floater = welcomeTextEl.closest(".welcome-floater");
  if (floater) {
    const computed = window.getComputedStyle(welcomeTextEl);
    const canvas = document.createElement("canvas");
    const ctx = canvas.getContext("2d");

    if (ctx) {
      ctx.font = `${computed.fontStyle} ${computed.fontVariant} ${computed.fontWeight} ${computed.fontSize} / ${computed.lineHeight} ${computed.fontFamily}`;
      const longest = greetings.reduce((max, phrase) => Math.max(max, ctx.measureText(phrase).width), 0);
      const floaterStyle = window.getComputedStyle(floater);
      const padX = parseFloat(floaterStyle.paddingLeft) + parseFloat(floaterStyle.paddingRight);
      const borderX = parseFloat(floaterStyle.borderLeftWidth) + parseFloat(floaterStyle.borderRightWidth);
      floater.style.width = `${Math.ceil(longest + padX + borderX + 2)}px`;
      floater.style.maxWidth = "calc(100vw - 20px)";
    }
  }

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

const supportForm = document.getElementById("supportForm");
if (supportForm) {
  const params = new URLSearchParams(window.location.search);
  const returnPath = params.get("return") || "supporters/";
  const intent = params.get("intent") || "general";

  const isProjectPath = window.location.pathname.includes("/SOSSite/");
  const basePrefix = isProjectPath ? "/SOSSite/" : "/";

  const formNext = document.getElementById("formNext");
  const formSubject = document.getElementById("formSubject");
  const formIntent = document.getElementById("formIntent");
  const formBackLink = document.getElementById("formBackLink");

  const normalizedReturn = returnPath.replace(/^\/+/, "");

  if (formBackLink) {
    formBackLink.href = `${basePrefix}${normalizedReturn}`;
  }

  if (formIntent) {
    formIntent.value = intent;
  }

  const subjectByIntent = {
    whatsapp: "#SOS form: WhatsApp chat join request",
    logo: "#SOS form: Organisation logo sharing request",
    inclusive: "#SOS form: Disability inclusion support request",
    donate: "#SOS form: Donation/in-kind support offer",
    general: "#SOS form submission",
  };

  if (formSubject) {
    formSubject.value = subjectByIntent[intent] || subjectByIntent.general;
  }
}


const redirectCountdownEl = document.getElementById("redirectCountdown");
if (redirectCountdownEl) {
  const targetLink = document.getElementById("thankYouTarget");
  const params = new URLSearchParams(window.location.search);
  const returnPath = params.get("return") || "supporters/";

  if (targetLink && params.get("return")) {
    const isProjectPath = window.location.pathname.includes("/SOSSite/");
    const basePrefix = isProjectPath ? "/SOSSite/" : "/";
    targetLink.href = `${basePrefix}${returnPath.replace(/^\/+/, "")}`;
  }

  let countdown = 3;
  redirectCountdownEl.textContent = String(countdown);

  const timer = setInterval(() => {
    countdown -= 1;
    redirectCountdownEl.textContent = String(Math.max(countdown, 0));

    if (countdown <= 0) {
      clearInterval(timer);
      const destination = targetLink ? targetLink.href : "supporters/";
      window.location.href = destination;
    }
  }, 1000);
}
