(function(){
  const $ = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));
  const badge = $('#donorBadge');

  // Tab switching
  function showTab(name){
    $$('#pane-plates, #pane-cart, #pane-purchased')
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
    if(j.status==='success' && j.user_type==='donor'){
      badge.textContent = `Donor MID ${j.mid}`;
    } else {
      badge.textContent = 'Forbidden: donor login required';
      document.body.innerHTML += '<div class="alert error">Please log in as donor to use this page.</div>';
    }
  }).catch(()=>{
    badge.textContent = 'Session check failed';
  });

  // Helpers
  function plateTable(headers, rows){
    let html = '<table class="table"><tr>' + headers.map(h=>`<th>${h}</th>`).join('') + '</tr>';
    for(const r of rows){
      html += '<tr>' + `<td>${r[0]}</td><td>$${r[1]}</td><td>${r[2]}</td><td>${r[3]}</td><td><button type="button" class="donate" id="${r[4]}">Donate</td>` + '</tr>';
    }
    html += '</table>';
    return html;
  }

  // Members search
  $('#formPlates').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const url = new URL('api_plates.php', location.href);
    const res = await fetch(url);
    const j = await res.json();
    if(j.status !== 'success'){ $('#platesResult').innerHTML = `<div class="alert error">${j.error||'Error'}</div>`; return; }
    const rows = j.rows.map(r=>[r.named, r.price, r.described||'', r. quantity, r.pid]);
    $('#platesResult').innerHTML = plateTable(['Plate','Price','Description','Quantity','Donate'], rows);
    
    const buttons = document.querySelectorAll('.donate');
    // Loop through the NodeList and add a click event listener to each button
    buttons.forEach(button => {
        button.addEventListener('click', function() {
          const url = new URL('api_donate.php', location.href);
          if(button.id) url.searchParams.set('pid', parseInt(button.id));
          const res = fetch(url);
        });
    })
  });

  // Initialize tab by hash
  const initial = (location.hash||'#plates').slice(1);
  showTab(initial);
})();
