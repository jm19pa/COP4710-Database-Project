(function(){
  const $ = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));
  const badge = $('#adminBadge');

  // Tab switching
  function showTab(name){
    $$('#pane-members, #pane-restaurant, #pane-purchases, #pane-needy, #pane-donor_tax')
      .forEach(p => p.hidden = !p.id.endsWith(name));
    $$('nav a').forEach(a => a.classList.toggle('active', a.dataset.tab===name));
    location.hash = name;
  }
  $$('nav a').forEach(a => a.addEventListener('click', e => {
    e.preventDefault();
    showTab(a.dataset.tab);
  }));

  // Session check: must be admin
  fetch('whoami.php').then(r=>r.json()).then(j=>{
    if(j.status==='success' && j.user_type==='admin'){
      badge.textContent = `Admin MID ${j.mid}`;
    } else {
      badge.textContent = 'Forbidden: admin login required';
      const prompt = document.createElement('div');
      prompt.className = 'alert error';
      prompt.innerHTML = 'Please log in as admin to use this page. ' +
        '<a class="button" style="margin-left:8px" href="/index.html">Go to Login</a>';
      document.body.appendChild(prompt);
    }
  }).catch(()=>{
    badge.textContent = 'Session check failed';
    const prompt = document.createElement('div');
    prompt.className = 'alert error';
    prompt.innerHTML = 'Session check failed. <a class="button" style="margin-left:8px" href="/index.html">Go to Login</a>';
    document.body.appendChild(prompt);
  });

  // Helpers
  function renderTable(headers, rows){
    let html = '<table class="table"><tr>' + headers.map(h=>`<th>${h}</th>`).join('') + '</tr>';
    for(const r of rows){
      html += '<tr>' + r.map(c=>`<td>${c}</td>`).join('') + '</tr>';
    }
    html += '</table>';
    return html;
  }

  // Members search
  $('#formMembers').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const q = e.target.q.value.trim();
    const type = e.target.type.value.trim();
    const url = new URL('api_members.php', location.href);
    if(q) url.searchParams.set('q', q);
    if(type) url.searchParams.set('type', type);
    const res = await fetch(url);
    const j = await res.json();
    if(j.status !== 'success'){ $('#membersResult').innerHTML = `<div class="alert error">${j.error||'Error'}</div>`; return; }
    const rows = j.rows.map(r=>[r.mid, r.username, r.email, r.user_type, r.named||'', r.phone||'']);
    $('#membersResult').innerHTML = renderTable(['MID','Username','Email','Type','Name','Phone'], rows);
  });

  // Restaurant activity
  $('#formRestaurant').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const year = parseInt(e.target.year.value||new Date().getFullYear(),10);
    const mid = parseInt(e.target.mid.value||'0',10);
    const url = new URL('api_restaurant_activity.php', location.href);
    url.searchParams.set('year', year); url.searchParams.set('mid', mid);
    const j = await (await fetch(url)).json();
    if(j.status!=='success'){ $('#restaurantResult').innerHTML = `<div class="alert error">${j.error||'Error'}</div>`; return; }
    $('#restaurantResult').innerHTML = `
      <div class="grid">
        <div class="card"><strong>Listed</strong><div>${j.listed}</div></div>
        <div class="card"><strong>Reserved</strong><div>${j.reserved}</div></div>
        <div class="card"><strong>Purchased</strong><div>${j.purchased}</div></div>
        <div class="card"><strong>Picked Up</strong><div>${j.picked}</div></div>
        <div class="card"><strong>Revenue ($)</strong><div>${Number(j.revenue).toFixed(2)}</div></div>
      </div>`;
  });

  // Purchases
  $('#formPurchases').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const year = parseInt(e.target.year.value||new Date().getFullYear(),10);
    const mid = parseInt(e.target.mid.value||'0',10);
    const url = new URL('api_purchases.php', location.href);
    url.searchParams.set('year', year); url.searchParams.set('mid', mid);
    const j = await (await fetch(url)).json();
    if(j.status!=='success'){ $('#purchasesResult').innerHTML = `<div class="alert error">${j.error||'Error'}</div>`; return; }
    let html = `
      <div class="grid">
        <div class="card"><strong>Total Items</strong><div>${j.items}</div></div>
        <div class="card"><strong>Total Spent ($)</strong><div>${Number(j.amount).toFixed(2)}</div></div>
      </div>`;
    if(j.months && j.months.length){
      const rows = j.months.map(m=>[m.ym, m.items, Number(m.amount).toFixed(2)]);
      html += renderTable(['Month','Items','Amount ($)'], rows);
    }
    $('#purchasesResult').innerHTML = html;
  });

  // Needy
  $('#formNeedy').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const year = parseInt(e.target.year.value||new Date().getFullYear(),10);
    const mid = parseInt(e.target.mid.value||'0',10);
    const url = new URL('api_needy.php', location.href);
    url.searchParams.set('year', year); url.searchParams.set('mid', mid);
    const j = await (await fetch(url)).json();
    if(j.status!=='success'){ $('#needyResult').innerHTML = `<div class="alert error">${j.error||'Error'}</div>`; return; }
    $('#needyResult').innerHTML = `
      <div class="grid">
        <div class="card"><strong>Total Plates</strong><div>${j.items}</div></div>
        <div class="card"><strong>Total Value ($)</strong><div>${Number(j.value).toFixed(2)}</div></div>
      </div>`;
  });

  // Donor tax
  $('#formDonorTax').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const year = parseInt(e.target.year.value||new Date().getFullYear(),10);
    const mid = parseInt(e.target.mid.value||'0',10);
    const url = new URL('api_donor_tax.php', location.href);
    url.searchParams.set('year', year); url.searchParams.set('mid', mid);
    const j = await (await fetch(url)).json();
    if(j.status!=='success'){ $('#donorTaxResult').innerHTML = `<div class="alert error">${j.error||'Error'}</div>`; return; }
    $('#donorTaxResult').innerHTML = `
      <div class="grid">
        <div class="card"><strong>Total Donated Items</strong><div>${j.items}</div></div>
        <div class="card"><strong>Total Donated ($)</strong><div>${Number(j.amount).toFixed(2)}</div></div>
        <div class="card"><strong>Picked Up Items</strong><div>${j.picked}</div></div>
        <div class="card"><strong>Picked Up Value ($)</strong><div>${Number(j.value).toFixed(2)}</div></div>
      </div>
      <small class="mono">Note: Donations are calculated from purchase totals; pickup stats show fulfillment.</small>`;
  });

  // Initialize tab by hash
  const initial = (location.hash||'#members').slice(1);
  showTab(initial);
  // Prefill current year
  const y = new Date().getFullYear();
  ['formRestaurant','formPurchases','formNeedy','formDonorTax'].forEach(id=>{ const f = document.getElementById(id); if(f) f.year.value=y; });
})();
