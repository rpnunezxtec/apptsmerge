// reviewdownload.js
(function(){
  // — Load agency & workflow —
  const agencyObj = JSON.parse(localStorage.getItem('xsitecraft-agencyname')||'{}');
  const agencyName = agencyObj.agencyName||'—';
  const workflows  = agencyObj.workflow||agencyObj.workflows||[];

  // — Load raw selected features & dedupe —
  const sel       = JSON.parse(localStorage.getItem('xsitecraft-selectfeatures')||'{}');
  const rawFeat   = Array.isArray(sel.features)? sel.features : [];
  const uniqueFeat = Array.from(new Set(rawFeat));

  // — Mandatory list —
  const mandatory = [
    'admin','appcore','applists','cred','credmgtutil','disclaimer',
    'portal','portal-cert','revoke','servicesasa','serviceswslcm',
    'signon','status','index.html','setup.sh','appconfig'
  ];

  // — For display: only those the user picked that aren’t mandatory —
  const uiFeatures = uniqueFeat.filter(f => !mandatory.includes(f));

  // — Load any image sections from sessionStorage —
  const sessionData = JSON.parse(sessionStorage.getItem('xsitecraft-finalData')||'{}');
  const imageEntries = Object.entries(sessionData)
    .filter(([k,v]) => v && v.name && v.dataUrl)
    .map(([key,{name}]) => ({ key, name }));

  // — Render the review box —
  const container = document.getElementById('dataReview');
  container.innerHTML = `
    <p><strong>Agency:</strong> ${agencyName}</p>
    <p><strong>Workflow:</strong> ${workflows.join(', ')||'—'}</p>
    <p><strong>Features:</strong> ${uiFeatures.length? uiFeatures.join(', '):'—'}</p>
    ${imageEntries.map(({key,name})=>{
      const label = key.charAt(0).toUpperCase()+key.slice(1);
      return `<p><strong>${label}:</strong> ${name}</p>`;
    }).join('')}
  `;

  // — Download handler bundles ALL features (mandatory + selected) —
  document.getElementById('downloadBtn').addEventListener('click',()=>{
    // Build download‐features: union of mandatory + selected
    const downloadFeatures = Array.from(
      new Set([...mandatory, ...uniqueFeat])
    );

    // Assemble the final JSON payload
    const finalData = {
      agencyName,
      workflow: workflows,
      features: downloadFeatures,
      ...Object.fromEntries(
        imageEntries.map(({key})=>[key,sessionData[key]])
      )
    };

    // Trigger download
    const filename = (agencyName||'xsitecraft') + '_data.json';
    const blob     = new Blob([JSON.stringify(finalData,null,2)],{type:'application/json'});
    const url      = URL.createObjectURL(blob);
    const a        = document.createElement('a');
    a.href     = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  });
})();
