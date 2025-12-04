/**
 * initFormState
 *  - Loads any existing data for this pageKey
 *  - On Next: runs HTML5 validation + optional extra, saves *all* fields, then onNext()
 *  - On Prev: saves *all* fields, then onPrev()
 *
 * @param {{ pageKey: string,
 *            onNext:   ()=>void,
 *            onPrev?:  ()=>void,
 *            validateFn?: (form:HTMLFormElement)=>boolean
 *          }} opts
 */
function initFormState({ pageKey, onNext, onPrev, validateFn }) {
  const form = document.getElementById('optionsForm');
  const next = document.getElementById('nextBtn');
  const prev = document.getElementById('prevBtn');

  //─── 1) Load & rehydrate ──────────────────────────────────────────
  (function loadState() {
    const raw = localStorage.getItem(pageKey);
    if (!raw) return;
    let data;
    try { data = JSON.parse(raw); }
    catch { return; }

    for (const [name, value] of Object.entries(data)) {
      const elems = form.elements[name];
      if (!elems) continue;

      // radios & checkboxes & multi-select become arrays
      if (Array.isArray(value)) {
        form.querySelectorAll(`[name="${name}"]`).forEach(i => {
          i.checked = value.includes(i.value);
          if (i.tagName === 'SELECT') {
            Array.from(i.options).forEach(opt => {
              opt.selected = value.includes(opt.value);
            });
          }
        });
      } else {
        // single-value inputs: text, number, select-one, radio
        if (elems.length !== undefined) {
          Array.from(elems).forEach(i => { if (i.value === value) i.checked = true; });
        } else {
          elems.value = value;
        }
      }
    }
  })();

  //─── 2) On Next: validate → save → navigate ────────────────────────
  next.addEventListener('click', e => {
    e.preventDefault();
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    if (validateFn && !validateFn(form)) {
      return;
    }
    saveAll();
    onNext();
  });

  //─── 3) On Prev: just save → navigate ─────────────────────────────
  if (prev && onPrev) {
    prev.addEventListener('click', e => {
      e.preventDefault();
      saveAll();
      onPrev();
    });
  }

  //─── helpers ───────────────────────────────────────────────────────
  function saveAll() {
    const out = {};

    // 1) Add hidden inputs
    form.querySelectorAll('input[type="hidden"]').forEach(input => {
      out[input.name] = out[input.name] || [];
      out[input.name].push(input.value);
    });

    // 2) Add checked checkboxes
    form.querySelectorAll('input[type="checkbox"]:checked').forEach(input => {
      out[input.name] = out[input.name] || [];
      out[input.name].push(input.value);
    });

    // 3) Add other fields (text, select, radio)
    const fd = new FormData(form);
    for (const [name, value] of fd.entries()) {
      if (form.querySelector(`[name="${name}"]`)?.type === 'checkbox') continue;
      out[name] = out[name] || [];
      out[name].push(value);
    }

    // 4) Flatten single-item arrays (except checkbox groups)
    for (const key in out) {
      const elems = form.elements[key];
      const isCheckboxGroup = elems instanceof RadioNodeList && elems[0]?.type === 'checkbox';
      if (out[key].length === 1 && !isCheckboxGroup) {
        out[key] = out[key][0];
      }
    }

    console.log('Saving to localStorage:', out);
    localStorage.setItem(pageKey, JSON.stringify(out));
  }
}
