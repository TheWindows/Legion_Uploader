const langBtn = document.getElementById("langBtn");

const translations = {
  en: {
    siteName: "Legion Transfer",
    navFeatures: "Features",
    navHow: "How it works",
    navPricing: "Pricing",
    navFaq: "FAQ",
    navUpload: "Upload",
    navLogin: "Login",
    navDownload: "Download",
    heroTitle: "Share files safely with a code",
    heroText: "Upload your file, set timer or password, then share the code.",
    btnUpload: "Upload file",
    btnDownload: "Download file",
    btnFeatures: "See features",
    stat1: "Max upload",
    stat2: "Max timer",
    stat3: "Share code",
    s1: "file is ready",
    s2: "Code: 1234567",
    s3: "For download enter the code",
    d1: "Download file",
    d2: "Enter the code that your friend sends you",
    d3: "Enter the file code",
    d4: "Search for file",
    featuresTitle: "Features",
    featuresText: "Everything you need for secure file sharing",
    f1: "Optional password",
    f1text: "Enable only if needed.",
    f2: "Auto delete timer",
    f2text: "1h / 3h / 12h / 24h.",
    f3: "Share by code",
    f3text: "Send the code to the receiver.",
    f4: "Dashboard",
    f4text: "View all your uploads.",
    f5: "1GB limit",
    f5text: "Per account.",
    f6: "Glassy UI",
    f6text: "Modern and beautiful look.",
    howTitle: "How it works",
    howText: "3 simple steps",
    step1: "Upload file",
    step1text: "Choose file and set timer/password.",
    step2: "Get code",
    step2text: "Copy the generated code.",
    step3: "Share & download",
    step3text: "Give the code and download.",
    pricingTitle: "Pricing",
    pricingText: "Free plan",
    priceTitle: "Free",
    priceText: "Max upload",
    priceBtn: "Create account",
    faqTitle: "FAQ",
    faqText: "Common questions",
    fe1: "Strong Password",
    fe2: "Auto delete timer",
    fe3: "Share file with code or link",
    fe4: "Well desgined dashboard",
    fe5: "Super fast download speed",
    fe6: "Strong security",
    fe7: "No need for credit card",
    priceTitlePremium: "Advanced",
    priceTextPremium: "Max upload",
    fe1P: "Strong Password",
    fe2P: "Auto delete timer up to 7 days",
    fe3P: "Share file with code or link",
    fe5P: "Super fast download speed",
    fe6P: "Better Encoding for security",
    fe7P: "25,000 Toman per month",
    priceBtnPremium: "Buy plan",
    q1: "How does timer work?",
    a1: "Files delete after selected time.",
    q2: "Can I upload without account?",
    a2: "No. You must login.",
    q3: "What if file is over 1GB?",
    a3: "Upload will fail. Keep under 1GB.",
    e1: "Invalid Code",
    e2: "Code must be 7 digits",
    footerText: "© 2026 Legion Transfer — Built By ❤ TheWindows",
  }
};

let currentLang = "fa";

if (langBtn) {
  langBtn.addEventListener("click", () => {
    if (currentLang === "fa") {
      currentLang = "en";
      langBtn.innerText = "فارسی";
      applyLang("en");
    } else {
      currentLang = "fa";
      langBtn.innerText = "English";
      location.reload();
    }
  });
}

function applyLang(lang) {
  const elements = [
    "siteName", "navFeatures", "navHow", "navPricing", "navDownload", "navFaq", 
    "navUpload", "navLogin", "heroTitle", "heroText", "btnUpload", "btnDownload",
    "btnFeatures", "stat1", "stat2", "stat3", "featuresTitle", 
    "featuresText", "f1", "f1text", "f2", "f2text", "f3", "f3text", 
    "f4", "f4text", "f5", "f5text", "f6", "f6text", "howTitle", 
    "howText", "step1", "step1text", "step2", "step2text", "step3", 
    "step3text", "pricingTitle", "pricingText", "priceTitle", 
    "priceText", "priceBtn", "faqTitle", "faqText", "q1", "a1", 
    "q2", "a2", "q3", "a3", "footerText", "s1", "s2", "s3", "d1", "d2", "d3", "d4",
    "fe1", "fe2", "fe3", "fe4", "fe5", "fe6", "fe7", "e1", "e2",
    "priceTitlePremium", "priceTextPremium", "fe1P", "fe2P", "fe3P", "fe5P",
    "fe6P", "fe7P", "priceBtnPremium"
  ];
  
  elements.forEach(id => {
    const element = document.getElementById(id);
    if (element && translations[lang][id]) {
      element.innerText = translations[lang][id];
    }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  const uploadForm = document.getElementById('uploadForm');
  if (uploadForm) {
    uploadForm.addEventListener('submit', function(e) {
      const fileInput = this.querySelector('input[type="file"]');
      const file = fileInput.files[0];
      
      if (!file) {
        e.preventDefault();
        alert('Please select a file.');
        return false;
      }
      
      if (file.size > 1024 * 1024 * 1024) {
        e.preventDefault();
        alert('File size cannot exceed 1GB.');
        return false;
      }
      
      return true;
    });
  }
  
  const downloadForm = document.getElementById('downloadForm');
  if (downloadForm) {
    downloadForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const code = document.getElementById('fileCode').value.trim();
      const resultDiv = document.getElementById('downloadResult');
      
      if (!code || code.length !== 7 || isNaN(code)) {
        resultDiv.innerHTML = `
          <div style="background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.3); border-radius: 10px; padding: 20px; text-align: center;">
            <div style="font-size: 24px; margin-bottom: 10px;">❌</div>
            <h4 style="color: #ff6b6b; margin-bottom: 10px;" id="e1">کد وجود ندارد</h4>
            <p style="color: rgba(255,255,255,0.7);" id="e2">کد باید حتما 7 عدد باشد</p>
          </div>
        `;
        return;
      }
      
      window.location.href = 'download.php?code=' + code;
    });
  }
  
  const dashboardDownloadForm = document.getElementById('dashboardDownloadForm');
  if (dashboardDownloadForm) {
    dashboardDownloadForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const code = document.getElementById('dashboardFileCode').value.trim();
      const resultDiv = document.getElementById('dashboardDownloadResult');
      
      if (!code || code.length !== 7 || isNaN(code)) {
        resultDiv.innerHTML = `
          <div style="background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.3); border-radius: 10px; padding: 15px; color: #ff6b6b; text-align: center;">
            Code must be a 7-digit number
          </div>
        `;
        return;
      }
      
      window.location.href = 'download.php?code=' + code;
    });
  }
  
  const emojiElements = document.querySelectorAll('.emoji-hover');
  emojiElements.forEach(emoji => {
    emoji.addEventListener('mouseenter', function() {
      this.style.filter = 'brightness(1.2)';
      this.style.transform = 'scale(1.1)';
    });
    
    emoji.addEventListener('mouseleave', function() {
      this.style.filter = '';
      this.style.transform = '';
    });
  });
  function handleDownloadForm() {
    const downloadForm = document.getElementById('downloadForm');
    const downloadFormDashboard = document.getElementById('dashboardDownloadForm');
    
    if (downloadForm) {
        downloadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const code = document.getElementById('fileCode').value.trim();
            if (code && code.length === 7 && !isNaN(code)) {
                window.location.href = 'download.php?code=' + code;
            } else {
                document.getElementById('downloadResult').innerHTML = `
                    <div style="background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.3); border-radius: 10px; padding: 15px; color: #ff6b6b; text-align: center;">
                        کد باید یک عدد 7 رقمی باشد
                    </div>
                `;
            }
        });
    }
    
    if (downloadFormDashboard) {
        downloadFormDashboard.addEventListener('submit', function(e) {
            e.preventDefault();
            const code = document.getElementById('dashboardFileCode').value.trim();
            if (code && code.length === 7 && !isNaN(code)) {
                window.location.href = 'download.php?code=' + code;
            } else {
                document.getElementById('dashboardDownloadResult').innerHTML = `
                    <div style="background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.3); border-radius: 10px; padding: 15px; color: #ff6b6b; text-align: center;">
                        کد باید یک عدد 7 رقمی باشد
                    </div>
                `;
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    handleDownloadForm();
});
});