(function(){
  const $ = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));
  const badge = $('#customerBadge');

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
    if(j.status==='success' && j.user_type==='customer'){
      badge.textContent = `Customer MID ${j.mid}`;
    } else {
      badge.textContent = 'Forbidden: customer login required';
      document.body.innerHTML += '<div class="alert error">Please log in as customer to use this page.</div>';
    }
  }).catch(()=>{
    badge.textContent = 'Session check failed';
  });

  // Helpers
  function plateTable(headers, rows){
    let html = '<table class="table"><tr>' + headers.map(h=>`<th>${h}</th>`).join('') + '</tr>';
    for(const r of rows){
      html += '<tr>' + r.map(c=>`<td>${c.named}</td><td>$${c.price}</td><td>${c.described}</td><td>${c.quantity}</td><td><button type="button" class="reserve" id="${c.pid}">Reserve</td>`).join('') + '</tr>';
    }
    html += '</table>';
    return html;
  }

  // Helpers
  function reserveTable(headers, rows){
    let html = '<table class="table"><tr>' + headers.map(h=>`<th>${h}</th>`).join('') + '</tr>';
    for(const r of rows){
      html += '<tr>' + r.map(c=>`<td>${c.named}</td><td>$${c.price}</td><td>${c.described}</td><td>${c.quantity}</td><td><button type="button" class="pickup" id="${c.pid}">Reserve</td>`).join('') + '</tr>';
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
    $('#platesResult').innerHTML = plateTable(['Plate','Price','Description','Quantity','Pick Up'], rows);
    
    const buttons = document.querySelectorAll('.reserve');
    // Loop through the NodeList and add a click event listener to each button
    buttons.forEach(button => {
        button.addEventListener('click', function() {
          const url = new URL('api_reserve.php', location.href);
          if(button.id) url.searchParams.set('pid', parseInt(button.id));
          const res = fetch(url);
        });
    })
  });

  // Restaurant activity
  $('#formReserved').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const url = new URL('api_reserved.php', location.href);
    const res = await fetch(url);
    const j = await res.json();
    if(j.status !== 'success'){ $('#reservedResult').innerHTML = `<div class="alert error">${j.error||'Error'}</div>`; return; }
    const rows = j.rows.map(r=>[r.named, r.price, r.described||'', r. quantity, r.pid]);
    $('#platesResult').innerHTML = reserveTable(['Plate','Price','Description','Quantity','Pick Up'], rows);
    
    const buttons = document.querySelectorAll('.pickup');
    // Loop through the NodeList and add a click event listener to each button
    buttons.forEach(button => {
        button.addEventListener('click', function() {
          const url = new URL('api_pick_up.php', location.href);
          if(button.id) url.searchParams.set('pid', parseInt(button.id));
          const res = fetch(url);
        });
    })
  });

  // Initialize tab by hash
  const initial = (location.hash||'#plates').slice(1);
  showTab(initial);
})();
