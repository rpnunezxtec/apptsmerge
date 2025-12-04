// 1) Exactly the pageKeys you used when initFormState ran
const pageKeys = [
  "xsitecraft-neworresume",
  "index.html",
  "xsitecraft-selectfeatures",
  "xsitecraft-questionnaire",
  "xsitecraft-reviewdownload",
  // I need to add here every time a new page is added*****
];

// 2) Pull them all into finalData
const finalData = {};

// Function to deep merge data
function deepMerge(target, source) {
  Object.keys(source).forEach((key) => {
    if (source[key] instanceof Object && !(source[key] instanceof Array)) {
      if (!target[key]) Object.assign(target, { [key]: {} });
      deepMerge(target[key], source[key]);
    } else {
      Object.assign(target, { [key]: source[key] });
    }
  });
}

pageKeys.forEach((key) => {
  const raw = localStorage.getItem(key);
  if (raw) {
    try {
      const data = JSON.parse(raw);
      deepMerge(finalData, data); // Use deepMerge to handle nested data
    } catch (e) {
      console.error(`Parsing error for ${key}:`, e);
    }
  }
});

// Debug: make sure we have data
console.log("Merged finalData:", finalData);

// 3) Render *all* entries, no hard-coded order needed
function renderReview(data) {
  const container = document.getElementById("dataReview");
  container.innerHTML = "";

  const keys = Object.keys(data);
  if (keys.length === 0) {
    container.textContent = "No data found. Please complete all steps.";
    return;
  }

  keys.forEach((key) => {
    const row = document.createElement("div");
    row.className = "data-row";

    const lbl = document.createElement("span");
    lbl.className = "data-label";
    // turn camelCase into title case for readability
    lbl.textContent =
      key.replace(/([A-Z])/g, " $1").replace(/^./, (s) => s.toUpperCase()) +
      ": ";

    const val = document.createElement("span");
    const v = data[key];
    val.textContent = Array.isArray(v) ? v.join(", ") : v;

    row.append(lbl, val);
    container.append(row);
  });
}

renderReview(finalData);

// 4) Download logic
async function downloadJSON() {
  // 1. Ensure all steps are complete
  for (const key of pageKeys) {
    if (!localStorage.getItem(key)) {
      alert(`⚠️ Missing data for step "${key}". Please complete that step.`);
      return;
    }
  }

  const dataStr = JSON.stringify(finalData, null, 2);

  // 2. If the File System Access API is available…
  if ("showSaveFilePicker" in window) {
    try {
      // 2a. Configure the save dialog
      const opts = {
        suggestedName: "xsitecraft_data.json",
        types: [
          {
            description: "JSON Files",
            accept: { "application/json": [".json"] },
          },
        ],
      };

      // 2b. Show the native Save File picker
      const handle = await window.showSaveFilePicker(opts);

      // 2c. Create a writable stream and write the JSON
      const writable = await handle.createWritable();
      await writable.write(dataStr);
      await writable.close();

      // (Optional) give user feedback
      console.log("File saved via File System Access API");
      return;
    } catch (err) {
      // User cancelled or an error occurred
      console.error(err);
      alert("Save cancelled or failed. Falling back to download.");
    }
  }

  // 3. Fallback for other browsers: ask for filename, then download
  const name =
    prompt("Enter a filename:", "xsitecraft_data.json") ||
    "xsitecraft_data.json";
  const blob = new Blob([dataStr], { type: "application/json" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = name;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

// wire up the button
document.getElementById("downloadBtn").addEventListener("click", downloadJSON);

const btn = document.getElementById("toggleSelectBtn");
btn.addEventListener("click", () => {
  // 1) Grab all the checkboxes you care about
  const cbs = Array.from(document.querySelectorAll('input[type="checkbox"]'));

  // 2) Are they *all* already checked?
  const allChecked = cbs.length > 0 && cbs.every((cb) => cb.checked);

  // 3) Toggle: if all were checked, uncheck; otherwise check all
  cbs.forEach((cb) => (cb.checked = !allChecked));

  // 4) Update button label for clarity
  btn.textContent = allChecked ? "Select All" : "Deselect All";
});
