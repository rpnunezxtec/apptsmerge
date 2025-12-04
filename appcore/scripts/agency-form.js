// 1. Normalize agency name input
document.getElementById("agencyName").addEventListener("input", function () {
  this.value = this.value.replace(/\s+/g, "").toLowerCase();
});

// 2. Setup form state and navigation logic
initFormState({
  pageKey: "index.html",
  onNext: () => {
    const checkboxes = document.querySelectorAll(
      'input[name="workflow"]:checked'
    );
    if (checkboxes.length === 0) {
      alert("Please select at least one workflow before continuing.");
      return;
    }
    window.location.href = "http://127.0.0.1:5500/selectfeatures.html";
  },
});

// 3. Attach event to the "Next" button that triggers the saved state flow
document.getElementById("nextBtn").addEventListener("click", () => {
  // This event is assumed to trigger whatever mechanism initFormState listens for
  document.dispatchEvent(new Event("xsitecraft-next"));
});
