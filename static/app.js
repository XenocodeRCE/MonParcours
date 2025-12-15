function formatNumber(n){ return new Intl.NumberFormat('fr-FR').format(n); }
function formatPercent(p){ if (p == null) return '—'; return new Intl.NumberFormat('fr-FR', { style: 'percent', maximumFractionDigits: 1 }).format(p); }

function computeMetrics(integrations, propositions, postulations, candidats){
  // returns rates/ratios (numbers) or null when undefined
  const safe = (a,b) => (b ? a / b : null);
  return {
    integration_rate: safe(integrations, candidats),
    proposition_rate: safe(propositions, candidats),
    postulation_rate: safe(postulations, candidats),
    integration_per_proposition: safe(integrations, propositions),
    applications_per_integration: safe(candidats, integrations),
    applications_per_proposition: safe(candidats, propositions),
  };
}

// Load data from generated window.ENRICHED or fallback to empty
const ENRICHED = window.ENRICHED || {};
const formations = Object.entries(ENRICHED).map(([name, info]) => {
  const t = info.totaux || {};
  const metrics = computeMetrics(t.integrations || 0, t.propositions || 0, t.postulations || 0, t.candidats || 0);
  return {
    name,
    integrations: t.integrations || 0,
    candidats: t.candidats || 0,
    propositions: t.propositions || 0,
    postulations: t.postulations || 0,
    ids_count: Object.keys(info.ids || {}).length,
    metrics
  };
});

let select, search, title, summary, idsList, detailsCard, showIds;
let barCtx, pieCtx;

function initApp(){
  select = document.getElementById('formationSelect');
  search = document.getElementById('search');
  title = document.getElementById('title');
  summary = document.getElementById('summary');
  idsList = document.getElementById('idsList');
  detailsCard = document.getElementById('detailsCard');
  showIds = document.getElementById('showIds');

  const barEl = document.getElementById('barChart');
  const pieEl = document.getElementById('pieChart');
  if (!barEl || !pieEl){
    console.warn('Chart canvas elements not found in DOM — charts disabled');
  } else {
    barCtx = barEl.getContext('2d');
    pieCtx = pieEl.getContext('2d');
  }

  // attach listeners
  select.addEventListener('change', onSelectChange);
  search.addEventListener('input', onSearchInput);
  showIds.addEventListener('change', ()=>{ if (select.value) select.dispatchEvent(new Event('change')); });

  // initial populate
  fillSelect(formations);
  if (select.options.length>0){ select.selectedIndex=0; select.dispatchEvent(new Event('change')); }
}

// ensure init runs when DOM is ready
if (document.readyState === 'loading'){
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}

function createBarChart(labels, data){
  if (window.barChart) window.barChart.destroy();
  window.barChart = new Chart(barCtx, { type: 'bar', data: { labels, datasets: [{ label: 'Valeurs', data, backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545'] }] }, options: { responsive:true } });
}
function createPieChart(data){
  if (window.pieChart) window.pieChart.destroy();
  window.pieChart = new Chart(pieCtx, { type: 'pie', data: { labels:['Intégrations','Propositions','Postulations'], datasets:[{ data, backgroundColor:['#0d6efd','#198754','#ffc107'] }] }, options:{responsive:true} });
}

function fillSelect(list){
  select.innerHTML='';
  list.forEach(f=>{
    const opt = document.createElement('option'); opt.value=f.name; opt.textContent = `${f.name} (${formatNumber(f.candidats)} candidatures)`; select.appendChild(opt);
  });
}

function onSelectChange(){
  const name = select.value; const info = ENRICHED[name];
  title.textContent = name;
  const t = info.totaux || {};
  const m = computeMetrics(t.integrations||0, t.propositions||0, t.postulations||0, t.candidats||0);

  // Summary with formatted numbers and key rates
  summary.innerHTML = `Integrations: <strong>${formatNumber(t.integrations||0)}</strong> (${formatPercent(m.integration_rate)}) &nbsp;|&nbsp; Candidatures: <strong>${formatNumber(t.candidats||0)}</strong> &nbsp;|&nbsp; Propositions: <strong>${formatNumber(t.propositions||0)}</strong> (${formatPercent(m.proposition_rate)}) &nbsp;|&nbsp; Postulations: <strong>${formatNumber(t.postulations||0)}</strong>`;
  summary.innerHTML += `<br><small class="text-muted">Taux d'intégration: ${formatPercent(m.integration_rate)} • Int./Prop.: ${m.integration_per_proposition? m.integration_per_proposition.toFixed(2) : '—'} • Candidats/par int.: ${m.applications_per_integration? m.applications_per_integration.toFixed(2) : '—'}</small>`;

  createBarChart(['Intégrations','Candidats','Propositions','Postulations'], [t.integrations||0, t.candidats||0, t.propositions||0, t.postulations||0]);
  createPieChart([t.integrations||0, t.propositions||0, t.postulations||0]);

  if (showIds.checked){ 
    detailsCard.style.display=''; 
    idsList.innerHTML=''; 
    Object.keys(info.ids||{}).forEach(id=>{ 
      const ii = info.ids[id];
      const im = ii.metrics || computeMetrics(ii.integrations||0, ii.propositions||0, ii.postulations||0, ii.candidats||0);
      const d=document.createElement('div'); 
      d.innerHTML = `<strong>${id}</strong> — int: ${formatNumber(ii.integrations||0)} (${formatPercent(im.integration_rate)}) • prop: ${formatNumber(ii.propositions||0)} (${formatPercent(im.proposition_rate)}) • cand: ${formatNumber(ii.candidats||0)}`;
      idsList.appendChild(d); 
    }); 
  } else { detailsCard.style.display='none'; }
}

function onSearchInput(){
  const q = search.value.toLowerCase(); fillSelect(formations.filter(f=> (f.name + ' ' + f.candidats).toLowerCase().includes(q) ));
}


