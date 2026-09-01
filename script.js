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

const comingSoonDonationLinks = document.querySelectorAll("[data-coming-soon-donation]");
comingSoonDonationLinks.forEach((link) => {
  link.addEventListener("click", (event) => {
    event.preventDefault();
    alert("This donation feature is not implemented yet.");
  });
});

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

  const selectWrap = document.createElement("div");
  selectWrap.className = "theme-select-wrap";

  const defaultDisplay = document.createElement("span");
  defaultDisplay.className = "theme-default-display";
  defaultDisplay.textContent = "Visual accessibility";
  defaultDisplay.setAttribute("aria-hidden", "true");

  function updateDefaultDisplay() {
    const isDefault = select.value === "default";
    select.classList.toggle("theme-select-default", isDefault);
    defaultDisplay.hidden = !isDefault;
  }

  select.value = initialTheme;
  updateDefaultDisplay();
  select.addEventListener("change", (event) => {
    const nextTheme = event.target.value;
    if (!THEMES.has(nextTheme)) return;
    applyTheme(nextTheme);
    localStorage.setItem(THEME_KEY, nextTheme);
    updateDefaultDisplay();
  });

  wrap.appendChild(label);
  selectWrap.appendChild(select);
  selectWrap.appendChild(defaultDisplay);
  wrap.appendChild(selectWrap);
  document.body.appendChild(wrap);
}

const savedTheme = localStorage.getItem(THEME_KEY);
const initialTheme = THEMES.has(savedTheme) ? savedTheme : "default";
applyTheme(initialTheme);
createThemeControl(initialTheme);

const MAGNIFICATION_KEY = "sos-font-magnification";
const MIN_MAGNIFICATION = 70;
const MAX_MAGNIFICATION = 200;
const MAGNIFICATION_STEP = 10;

function normaliseMagnification(value) {
  const parsed = Number.parseInt(value, 10);
  if (!Number.isFinite(parsed)) return 100;
  const stepped = Math.round(parsed / MAGNIFICATION_STEP) * MAGNIFICATION_STEP;
  return Math.min(MAX_MAGNIFICATION, Math.max(MIN_MAGNIFICATION, stepped));
}

function applyMagnification(value) {
  document.documentElement.style.fontSize = `${value}%`;
  window.dispatchEvent(new CustomEvent("sos:magnification-change"));
}

function createMagnificationControl(initialValue) {
  let currentValue = initialValue;

  const wrap = document.createElement("div");
  wrap.className = "magnification-floater";

  const label = document.createElement("span");
  label.className = "magnification-label";
  label.textContent = "Magnification";

  const controls = document.createElement("div");
  controls.className = "magnification-controls";

  const decreaseButton = document.createElement("button");
  decreaseButton.className = "magnification-button";
  decreaseButton.type = "button";
  decreaseButton.textContent = "-";

  const valueDisplay = document.createElement("span");
  valueDisplay.className = "magnification-value";
  valueDisplay.setAttribute("aria-live", "polite");

  const increaseButton = document.createElement("button");
  increaseButton.className = "magnification-button";
  increaseButton.type = "button";
  increaseButton.textContent = "+";

  const resetButton = document.createElement("button");
  resetButton.className = "magnification-reset";
  resetButton.type = "button";
  resetButton.textContent = "Reset";
  resetButton.setAttribute("aria-label", "Reset magnification to 100%");

  function updateControl() {
    valueDisplay.textContent = `${currentValue}%`;
    decreaseButton.disabled = currentValue <= MIN_MAGNIFICATION;
    increaseButton.disabled = currentValue >= MAX_MAGNIFICATION;
    resetButton.disabled = currentValue === 100;
    decreaseButton.setAttribute(
      "aria-label",
      decreaseButton.disabled
        ? `Minimum magnification is ${MIN_MAGNIFICATION}%`
        : `Decrease magnification to ${currentValue - MAGNIFICATION_STEP}%`
    );
    increaseButton.setAttribute(
      "aria-label",
      increaseButton.disabled
        ? `Maximum magnification is ${MAX_MAGNIFICATION}%`
        : `Increase magnification to ${currentValue + MAGNIFICATION_STEP}%`
    );
    applyMagnification(currentValue);
    localStorage.setItem(MAGNIFICATION_KEY, String(currentValue));
  }

  decreaseButton.addEventListener("click", () => {
    currentValue = normaliseMagnification(currentValue - MAGNIFICATION_STEP);
    updateControl();
  });

  increaseButton.addEventListener("click", () => {
    currentValue = normaliseMagnification(currentValue + MAGNIFICATION_STEP);
    updateControl();
  });

  resetButton.addEventListener("click", () => {
    currentValue = 100;
    updateControl();
  });

  controls.appendChild(decreaseButton);
  controls.appendChild(valueDisplay);
  controls.appendChild(increaseButton);
  wrap.appendChild(label);
  wrap.appendChild(controls);
  wrap.appendChild(resetButton);
  document.body.appendChild(wrap);
  updateControl();
}

const initialMagnification = normaliseMagnification(localStorage.getItem(MAGNIFICATION_KEY));
applyMagnification(initialMagnification);
createMagnificationControl(initialMagnification);


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
    const canvas = document.createElement("canvas");
    const ctx = canvas.getContext("2d");

    function resizeWelcomeFloater() {
      if (!ctx) return;
      const computed = window.getComputedStyle(welcomeTextEl);
      ctx.font = `${computed.fontStyle} ${computed.fontVariant} ${computed.fontWeight} ${computed.fontSize} / ${computed.lineHeight} ${computed.fontFamily}`;
      const longest = greetings.reduce((max, phrase) => Math.max(max, ctx.measureText(phrase).width), 0);
      const floaterStyle = window.getComputedStyle(floater);
      const padX = parseFloat(floaterStyle.paddingLeft) + parseFloat(floaterStyle.paddingRight);
      const borderX = parseFloat(floaterStyle.borderLeftWidth) + parseFloat(floaterStyle.borderRightWidth);
      floater.style.width = `${Math.ceil(longest + padX + borderX + 2)}px`;
      floater.style.maxWidth = "calc(100vw - 20px)";
    }

    resizeWelcomeFloater();
    window.addEventListener("sos:magnification-change", () => {
      window.requestAnimationFrame(resizeWelcomeFloater);
    });
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
  const duplicateMapLayers = africaMap.querySelectorAll('.land[id^="path4"], .land[id^="g4"], .circle[id^="path4"], .circle[id^="g4"]');

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

  const flagOverrides = {
    "es-cn": "🇪🇸",
    "pt-30": "🇵🇹",
  };

  const getFlagEmoji = (code) => {
    if (flagOverrides[code]) return flagOverrides[code];
    if (!/^[a-z]{2}$/.test(code)) return "";

    return code
      .toUpperCase()
      .split("")
      .map((letter) => String.fromCodePoint(127397 + letter.charCodeAt(0)))
      .join("");
  };

  let activeCode = null;
  let lastFocus = null;

  const openModal = (name, code, targetEl) => {
    if (!modal || !modalTitle || !modalBody) return;
    lastFocus = targetEl || document.activeElement;
    const flag = getFlagEmoji(code);
    const flagEl = document.createElement("span");
    flagEl.className = "map-modal-flag";
    flagEl.setAttribute("aria-hidden", "true");
    flagEl.textContent = flag;

    const nameEl = document.createElement("span");
    nameEl.textContent = name;

    modalTitle.replaceChildren(flagEl, nameEl);
    modalTitle.setAttribute("aria-label", name);
    modalBody.textContent = "Details coming soon.";
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
  const pursuingCountries = new Set([]);
  const interactiveCountries = new Set([
    ...focusCountries,
    ...pursuingCountries,
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

    if (interactiveCountries.has(code)) {
      el.classList.add("clickable");
    }
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

      if (pursuingCountries.has(code)) {
        layer.classList.add("country-pursuing");
      }

      layer.setAttribute("aria-label", name);
      layer.dataset.country = name;

      if (interactiveCountries.has(code)) {
        layer.addEventListener("click", () => {
          setActiveCountry(code);
          openModal(name, code, primaryLayer);
        });
      }
    });

    if (interactiveCountries.has(code)) {
      primaryLayer.setAttribute("tabindex", "0");
      primaryLayer.setAttribute("role", "button");
    }

    const existingTitle = primaryLayer.querySelector("title");
    if (existingTitle) {
      existingTitle.textContent = name;
    } else {
      const titleEl = document.createElementNS("http://www.w3.org/2000/svg", "title");
      titleEl.textContent = name;
      primaryLayer.appendChild(titleEl);
    }

    if (interactiveCountries.has(code)) {
      primaryLayer.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          setActiveCountry(code);
          openModal(name, code, primaryLayer);
        }
      });
    }
  });
}

const ukMap = document.getElementById("uk-map");
if (ukMap) {
  const modal = document.getElementById("mapModal");
  const modalTitle = document.getElementById("mapModalTitle");
  const modalBody = document.getElementById("mapModalBody");
  const closeTargets = modal ? modal.querySelectorAll("[data-close='true']") : [];
  const countryLayers = [
    { id: "layer6", code: "wales", name: "Wales", fill: "#b9dcff" },
  ];

  let activeCountry = null;
  let lastFocus = null;

  const clearActiveCountry = () => {
    if (activeCountry) {
      activeCountry.querySelectorAll("path").forEach((path) => {
        path.style.fill = activeCountry.dataset.fill;
        path.style.stroke = "#2b2b2b";
      });
      activeCountry = null;
    }
  };

  const openUkModal = (country, targetEl) => {
    if (!modal || !modalTitle || !modalBody) return;
    lastFocus = targetEl || document.activeElement;
    clearActiveCountry();
    activeCountry = targetEl;
    activeCountry.querySelectorAll("path").forEach((path) => {
      path.style.fill = "#082555";
      path.style.stroke = "#082555";
    });
    modalTitle.textContent = country;
    modalTitle.setAttribute("aria-label", country);
    modalBody.textContent = "Details coming soon.";
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    const closeBtn = modal.querySelector(".map-modal-close");
    if (closeBtn) closeBtn.focus();
  };

  const closeUkModal = () => {
    if (!modal) return;
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    clearActiveCountry();
    if (lastFocus && typeof lastFocus.focus === "function") {
      lastFocus.focus();
    }
  };

  if (closeTargets && closeTargets.length) {
    closeTargets.forEach((target) => {
      target.addEventListener("click", closeUkModal);
    });
  }

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && modal && modal.classList.contains("is-open")) {
      closeUkModal();
    }
  });

  const setupUkMap = () => {
    const svgDoc = ukMap.contentDocument;
    if (!svgDoc) return;
    const svgEl = svgDoc.querySelector("svg");

    if (svgEl) {
      svgEl.setAttribute("role", "img");
      svgEl.setAttribute("aria-label", "Map of the United Kingdom by country");
    }

    countryLayers.forEach(({ id, code, name, fill }) => {
      const countryEl = svgDoc.getElementById(id);
      if (!countryEl) return;

      countryEl.id = code;
      countryEl.dataset.fill = fill;
      countryEl.setAttribute("tabindex", "0");
      countryEl.setAttribute("role", "button");
      countryEl.setAttribute("aria-label", name);

      countryEl.querySelectorAll("path").forEach((path) => {
        path.style.fill = fill;
        path.style.stroke = "#2b2b2b";
        path.style.strokeWidth = "2";
        path.style.cursor = "pointer";
        path.style.transition = "fill 0.15s ease, stroke 0.15s ease";
      });

      const titleEl = svgDoc.createElementNS("http://www.w3.org/2000/svg", "title");
      titleEl.textContent = name;
      countryEl.prepend(titleEl);

      countryEl.addEventListener("mouseenter", () => {
        if (activeCountry === countryEl) return;
        countryEl.querySelectorAll("path").forEach((path) => {
          path.style.fill = "#082555";
          path.style.stroke = "#082555";
        });
      });

      countryEl.addEventListener("mouseleave", () => {
        if (activeCountry === countryEl) return;
        countryEl.querySelectorAll("path").forEach((path) => {
          path.style.fill = fill;
          path.style.stroke = "#2b2b2b";
        });
      });

      countryEl.addEventListener("click", () => openUkModal(name, countryEl));
      countryEl.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          openUkModal(name, countryEl);
        }
      });
    });

    const styleEl = svgDoc.createElementNS("http://www.w3.org/2000/svg", "style");
    styleEl.textContent = "g[role='button']:focus{outline:none}";
    svgEl.appendChild(styleEl);
  };

  ukMap.addEventListener("load", setupUkMap);
  window.setTimeout(setupUkMap, 0);
}
// About page accordion
document.querySelectorAll(".accordion-header").forEach((header) => {
  header.addEventListener("click", () => {
    const item = header.parentElement;
    const isOpen = item.classList.contains("active");

    document.querySelectorAll(".accordion-item").forEach((accordion) => {
      accordion.classList.remove("active");
    });

    if (!isOpen) {
      item.classList.add("active");
    }
  });
});
