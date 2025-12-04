// imageupload.js

const MAX_SIZE = 5 * 1024 * 1024; // 5 MB
const MIN_DIM  = 10;              // 10×10 px

// 1) Find all file-inputs marked for image sections
const fileInputs = document.querySelectorAll('input.image-section[type="file"]');
const sections = Array.from(fileInputs).map(inputEl => {
  // derive key from id, e.g. "profileUpload" → "profile"
  const key = inputEl.id.replace(/Upload$/, '');
  const previewEl = document.getElementById(`${key}Preview`);
  return { key, inputEl, previewEl };
});

// 2) Load or initialize the shared storage object
const stored      = JSON.parse(sessionStorage.getItem('xsitecraft-finalData') || '{}');
const finalData   = { ...stored };

// ensure every section has a slot
sections.forEach(({ key }) => {
  if (!(key in finalData)) finalData[key] = null;
});

// 3) Render helper — shows the thumbnail if present
function renderSection({ key, previewEl }) {
  previewEl.innerHTML = '';
  const img = finalData[key];
  if (img && img.dataUrl) {
    previewEl.innerHTML = `
      <img
        src="${img.dataUrl}"
        alt="${img.name}"
        style="
          max-width:150px;
          max-height:150px;
          object-fit:contain;
          margin:0.5rem;
          border:1px solid #ccc;
          border-radius:4px;
        "
      />
    `;
  }
}

// initial render of all previews
sections.forEach(renderSection);

// 4) Wire up each input’s change handler
sections.forEach(({ key, inputEl, previewEl }) => {
  inputEl.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    if (file.size > MAX_SIZE) {
      alert(`"${file.name}" is over 5 MB and was skipped.`);
      this.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      const imgTest = new Image();
      imgTest.onload = () => {
        if (imgTest.width < MIN_DIM || imgTest.height < MIN_DIM) {
          alert(`"${file.name}" must be at least ${MIN_DIM}×${MIN_DIM}px.`);
          inputEl.value = '';
        } else {
          // save into our shared object
          finalData[key] = { name: file.name, dataUrl: reader.result };
          // persist all sections
          sessionStorage.setItem(
            'xsitecraft-finalData',
            JSON.stringify(finalData)
          );
          // update this preview
          renderSection({ key, previewEl });
        }
      };
      imgTest.src = reader.result;
    };
    reader.readAsDataURL(file);
    // clear so same file can be re-selected if needed
    this.value = '';
  });
});

// 5) Next button: validate required, then proceed
document.getElementById('nextBtn').addEventListener('click', () => {
  // Adjust these to require whichever keys you need:
  if (!finalData.profile) {
    alert('Please upload a User Profile image.');
    return;
  }
  if (!finalData.logo) {
    alert('Please upload a Logo image.');
    return;
  }
  // All done—save and navigate
  sessionStorage.setItem('xsitecraft-finalData', JSON.stringify(finalData));
  window.location.href = 'reviewdownload.html';
});
