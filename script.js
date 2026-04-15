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

  const submitButton = document.getElementById("supportSubmitBtn");
  let isSubmitting = false;

  const submitSupportForm = async () => {
    if (isSubmitting) return;
    isSubmitting = true;

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = "Submitting...";
    }

    const formData = new FormData(supportForm);

    try {
      const response = await fetch("https://formsubmit.co/ajax/matthewjackson688@gmail.com", {
        method: "POST",
        headers: {
          Accept: "application/json",
        },
        body: formData,
      });

      if (!response.ok) {
        throw new Error("Form submission failed");
      }

      const thankYouUrl = `${basePrefix}thank-you/?return=${encodeURIComponent(normalizedReturn)}`;
      window.location.href = thankYouUrl;
    } catch (error) {
      isSubmitting = false;
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = "Submit Form";
      }
      alert("There was a problem sending the form. Please try again.");
    }
  };

  supportForm.addEventListener("submit", (event) => {
    event.preventDefault();
    submitSupportForm();
  });

  if (submitButton) {
    submitButton.addEventListener("click", submitSupportForm);
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

const africaMap = document.getElementById("africa-map");
if (africaMap) {
  const modal = document.getElementById("mapModal");
  const modalTitle = document.getElementById("mapModalTitle");
  const modalBody = document.getElementById("mapModalBody");
  const closeTargets = modal ? modal.querySelectorAll("[data-close='true']") : [];
  const duplicateMapLayers = africaMap.querySelectorAll('[id^="path4"], [id^="g4"]');

  duplicateMapLayers.forEach((layer) => layer.remove());

  const countryNames = {
    ao: "Angola",
    bf: "Burkina Faso",
    bi: "Burundi",
    bj: "Benin",
    bw: "Botswana",
    cd: "Democratic Republic of the Congo",
    cf: "Central African Republic",
    cg: "Republic of the Congo",
    ci: "Cote d'Ivoire",
    cm: "Cameroon",
    cv: "Cabo Verde",
    dj: "Djibouti",
    dz: "Algeria",
    eg: "Egypt",
    eh: "Western Sahara",
    er: "Eritrea",
    "es-cn": "Canary Islands",
    et: "Ethiopia",
    ga: "Gabon",
    gh: "Ghana",
    gm: "Gambia",
    gn: "Guinea",
    gq: "Equatorial Guinea",
    gw: "Guinea-Bissau",
    ke: "Kenya",
    km: "Comoros",
    lr: "Liberia",
    ls: "Lesotho",
    ly: "Libya",
    ma: "Morocco",
    mg: "Madagascar",
    ml: "Mali",
    mr: "Mauritania",
    mu: "Mauritius",
    mw: "Malawi",
    mz: "Mozambique",
    na: "Namibia",
    ne: "Niger",
    ng: "Nigeria",
    "pt-30": "Madeira",
    re: "Reunion",
    rw: "Rwanda",
    sc: "Seychelles",
    sd: "Sudan",
    sh: "Saint Helena",
    sl: "Sierra Leone",
    sn: "Senegal",
    so: "Somalia",
    ss: "South Sudan",
    st: "Sao Tome and Principe",
    sz: "Eswatini",
    td: "Chad",
    tg: "Togo",
    tn: "Tunisia",
    tz: "Tanzania",
    ug: "Uganda",
    yt: "Mayotte",
    za: "South Africa",
    zm: "Zambia",
    zw: "Zimbabwe",
  };

  let activeCode = null;
  let lastFocus = null;

  const openModal = (name, code, targetEl) => {
    if (!modal || !modalTitle || !modalBody) return;
    lastFocus = targetEl || document.activeElement;
    modalTitle.textContent = name;
    const codeLabel = code ? code.toUpperCase() : "N/A";
    modalBody.textContent = `Country code: ${codeLabel}. Details coming soon.`;
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    const closeBtn = modal.querySelector(".map-modal-close");
    if (closeBtn) closeBtn.focus();
  };

  const closeModal = () => {
    if (!modal) return;
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    if (activeCode) {
      const activeLayers = africaMap.querySelectorAll(`[data-country-code="${activeCode}"]`);
      activeLayers.forEach((layer) => layer.classList.remove("is-active"));
      activeCode = null;
    }
    if (lastFocus && typeof lastFocus.focus === "function") {
      lastFocus.focus();
    }
  };

  if (closeTargets && closeTargets.length) {
    closeTargets.forEach((target) => {
      target.addEventListener("click", closeModal);
    });
  }

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && modal && modal.classList.contains("is-open")) {
      closeModal();
    }
  });

  const focusCountries = new Set([
    "cf",
    "gh",
    "ke",
    "ls",
    "ng",
    "rw",
    "tz",
    "ug",
    "za",
    "zm",
    "zw",
  ]);
  const countryCodes = new Set(Object.keys(countryNames));
  const countryLayers = new Map();

  const mapElements = africaMap.querySelectorAll(".land, .circle, [id]");
  mapElements.forEach((el) => {
    const id = (el.getAttribute("id") || "").toLowerCase();
    const classCode = Array.from(el.classList).find((cls) => countryCodes.has(cls));
    const code = countryCodes.has(id) ? id : classCode;

    if (!code) return;

    if (!countryLayers.has(code)) {
      countryLayers.set(code, []);
    }

    if (!el.classList.contains("land") && !el.classList.contains("circle")) {
      el.classList.add("land");
    }

    el.classList.add("clickable");
    el.dataset.countryCode = code;
    countryLayers.get(code).push(el);
  });

  const setActiveCountry = (code) => {
    if (activeCode) {
      const previousLayers = countryLayers.get(activeCode) || [];
      previousLayers.forEach((layer) => layer.classList.remove("is-active"));
    }

    activeCode = code;

    const nextLayers = countryLayers.get(code) || [];
    nextLayers.forEach((layer) => layer.classList.add("is-active"));
  };

  countryLayers.forEach((layers, code) => {
    const name = countryNames[code];
    const primaryLayer = layers[0];

    layers.forEach((layer) => {
      if (focusCountries.has(code)) {
        layer.classList.add("country-focus");
      }

      layer.setAttribute("aria-label", name);
      layer.dataset.country = name;

      layer.addEventListener("click", () => {
        setActiveCountry(code);
        openModal(name, code, primaryLayer);
      });
    });

    primaryLayer.setAttribute("tabindex", "0");
    primaryLayer.setAttribute("role", "button");

    const existingTitle = primaryLayer.querySelector("title");
    if (existingTitle) {
      existingTitle.textContent = name;
    } else {
      const titleEl = document.createElementNS("http://www.w3.org/2000/svg", "title");
      titleEl.textContent = name;
      primaryLayer.appendChild(titleEl);
    }

    primaryLayer.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        setActiveCountry(code);
        openModal(name, code, primaryLayer);
      }
    });
  });
}
