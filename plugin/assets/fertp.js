(() => {
  function $(sel, root=document){ return root.querySelector(sel); }
  function $all(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }
  function fertpRestBase(){
    let b = (window.FERTP && window.FERTP.rest) ? window.FERTP.rest : '';
    if(!b){ b = window.location.origin.replace(/\/$/, '') + '/wp-json/fert/v1/'; }
    if(b && !b.endsWith('/')) b += '/';
    return b;
  }
  async function fertpFetchJSON(url){
    const res = await fetch(url, {credentials:'same-origin'});
    if(!res.ok) throw new Error('HTTP '+res.status);
    return await res.json();
  }
  function isMobileDevice(){ return window.innerWidth < 768; }
  async function renderChart(canvas, slug, days, label){
    const wrap = canvas?.closest?.('.fertp-chart-wrap');
    if(!window.Chart){ if(wrap){ wrap.innerHTML = '<div style="padding:16px;color:#6b7280">图表组件未加载。</div>'; } return; }
    const Chart = window.Chart;
    const ctx = canvas.getContext('2d');
    if(canvas.__fertpChart){ try{ canvas.__fertpChart.destroy(); }catch(e){} canvas.__fertpChart = null; }
    let url = `${fertpRestBase()}prices?slug=${encodeURIComponent(slug)}&days=${encodeURIComponent(days)}`;
    let data;
    try{ data = await fertpFetchJSON(url); }catch(e){ if(wrap){ wrap.innerHTML = '<div style="padding:16px;color:#6b7280">趋势数据加载失败。</div>'; } return; }
    const series = (data && data.series) ? data.series : ((data && data.data) ? data.data : []);
    const rawLabels = (series || []).map(p => p.date || p.day || p.t || '');
    const labels = rawLabels.map(d => {
      if(!d) return '';
      const m = d.match(/(\d{4})[-\/](\d{2})[-\/](\d{2})/);
      if(m) return m[2] + '-' + m[3];
      return d;
    });
    const values = (series || []).map(p => {
      const raw = (p.price !== undefined) ? p.price : ((p.value !== undefined) ? p.value : ((p.close !== undefined) ? p.close : (p.y !== undefined ? p.y : '')));
      const n = Number(raw);
      return Number.isFinite(n) ? n : null;
    });
    const validValues = values.filter(v => v !== null && Number.isFinite(v));
    let yMin = undefined, yMax = undefined;
    if(validValues.length > 0){
      const dataMin = Math.min(...validValues);
      const dataMax = Math.max(...validValues);
      const padding = (dataMax - dataMin) * 0.15 || dataMin * 0.05 || 50;
      yMin = Math.floor((dataMin - padding) / 50) * 50;
      yMax = Math.ceil((dataMax + padding) / 50) * 50;
    }
    const isMobile = isMobileDevice();
    canvas.__fertpChart = new Chart(ctx, {
      type: 'line',
      data: { labels, datasets: [{ label: label || slug, data: values, tension: 0.25, fill: true, pointRadius: isMobile ? 4 : 2, borderWidth: isMobile ? 3 : 2, pointHoverRadius: isMobile ? 6 : 4 }] },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: true, position: isMobile ? 'bottom' : 'top', labels: { font: { size: isMobile ? 12 : 14 }, padding: isMobile ? 15 : 20 } } },
        scales: {
          x: { ticks: { maxTicksLimit: isMobile ? 6 : 10, font: { size: isMobile ? 10 : 12 }, maxRotation: isMobile ? 45 : 0, minRotation: isMobile ? 45 : 0 } },
          y: { beginAtZero: false, min: yMin, max: yMax, ticks: { font: { size: isMobile ? 10 : 12 }, callback: function(v){ return v.toLocaleString(); } } }
        }
      }
    });
  }
  function currentDays(){ const a = $('.fertp-range-btn.active'); return a ? Number(a.getAttribute('data-days')) : 90; }
  function activeSlug(d='urea'){ const a = $('.fertp-tab.active'); return a ? a.getAttribute('data-slug') : d; }
  function activeLabel(){ const a = $('.fertp-tab.active'); return a ? a.textContent.trim() : activeSlug(); }
  function ensureChartCanvas(){
    let canvas = document.getElementById('fertpChart');
    if(!canvas){ const wrap = document.querySelector('.fertp-chart-wrap'); if(wrap){ wrap.innerHTML = '<canvas id="fertpChart" height="120"></canvas>'; canvas = document.getElementById('fertpChart'); } }
    if(!canvas) return null;
    const wrap = canvas.closest('.fertp-chart-wrap');
    if(wrap){ const isMobile = window.innerWidth < 768; wrap.style.height = wrap.style.height || (isMobile ? '500px' : '320px'); }
    return canvas;
  }
  function bindChartTabs(){
    const canvas = ensureChartCanvas(); if(!canvas) return;
    $all('.fertp-tab').forEach(btn=>{ btn.addEventListener('click', async ()=>{ $all('.fertp-tab').forEach(b=>b.classList.remove('active')); btn.classList.add('active'); await renderChart(canvas, btn.getAttribute('data-slug'), currentDays(), btn.textContent.trim()); canvas.__fertpCurrentSlug = btn.getAttribute('data-slug'); }); });
    $all('.fertp-range-btn').forEach(btn=>{ btn.addEventListener('click', async ()=>{ $all('.fertp-range-btn').forEach(b=>b.classList.remove('active')); btn.classList.add('active'); await renderChart(canvas, activeSlug(), currentDays(), activeLabel()); canvas.__fertpCurrentSlug = activeSlug(); }); });
  }
  function bindFilterBar(){
    const bar = document.getElementById('fertpFilterBar'); if(!bar) return;
    const catTabs = $all('.fertp-filter-tab', bar);
    catTabs.forEach(t=>{ t.addEventListener('click', ()=>{ catTabs.forEach(x=>x.classList.remove('active')); t.classList.add('active'); applyCardFilters(); }); });
    document.getElementById('fertpRegion')?.addEventListener('change', applyCardFilters);
    document.getElementById('fertpSpec')?.addEventListener('change', applyCardFilters);
  }
  function selectedCategory(){ const t = document.querySelector('.fertp-filter-tab.active'); return t ? t.getAttribute('data-category') : 'all'; }
  function highlightCategoryTab(category){
    const bar = document.getElementById('fertpFilterBar'); if(!bar) return;
    const cat = category || 'all';
    const target = bar.querySelector(`.fertp-filter-tab[data-category="${cat}"]`) || bar.querySelector('.fertp-filter-tab[data-category="all"]');
    if(!target) return;
    $all('.fertp-filter-tab', bar).forEach(b=>b.classList.remove('active')); target.classList.add('active');
  }
  function autoChartFromFirstVisibleCard(){
    const canvas = ensureChartCanvas(); if(!canvas) return;
    const wrap = document.getElementById('fertpPriceCards'); if(!wrap) return;
    const first = $all('.fertp-card', wrap).find(c=>c.style.display !== 'none'); if(!first) return;
    const slug = first.getAttribute('data-slug'); if(!slug) return;
    if(canvas.__fertpCurrentSlug === slug) return;
    highlightCategoryTab(first.getAttribute('data-category') || 'all');
    renderChart(canvas, slug, currentDays(), activeLabel()); canvas.__fertpCurrentSlug = slug;
  }
  function applyCardFilters(){
    const wrap = document.getElementById('fertpPriceCards'); if(!wrap) return;
    const cat = selectedCategory(); const region = document.getElementById('fertpRegion')?.value || 'all'; const spec = document.getElementById('fertpSpec')?.value || 'all';
    $all('.fertp-card', wrap).forEach(card=>{ const okCat = (cat==='all')||(card.getAttribute('data-category')===cat); const okRegion = (region==='all')||(card.getAttribute('data-region')===region); const okSpec = (spec==='all')||(card.getAttribute('data-spec')===spec); card.style.display = (okCat&&okRegion&&okSpec) ? '' : 'none'; });
    autoChartFromFirstVisibleCard();
  }
  function bindCardClickSync(){
    const wrap = document.getElementById('fertpPriceCards'); const canvas = ensureChartCanvas(); if(!wrap||!canvas) return;
    wrap.addEventListener('click', (e)=>{ const card = e.target.closest('.fertp-card'); if(!card) return; const slug = card.getAttribute('data-slug'); if(!slug) return; highlightCategoryTab(card.getAttribute('data-category')||'all'); const t = document.querySelector(`.fertp-tab[data-slug="${slug}"]`); if(t){ $all('.fertp-tab').forEach(b=>b.classList.remove('active')); t.classList.add('active'); } renderChart(canvas, slug, currentDays(), (card.querySelector('.fertp-card-title')?.textContent||slug).trim()); canvas.__fertpCurrentSlug = slug; canvas.closest('.fertp-chart-wrap')?.scrollIntoView?.({behavior:'smooth',block:'start'}); });
  }
  window.addEventListener('resize', ()=>{ const canvas = document.getElementById('fertpChart'); if(canvas&&canvas.__fertpChart){ renderChart(canvas, canvas.__fertpCurrentSlug||activeSlug('urea'), currentDays(), activeLabel()); } });
  document.addEventListener('DOMContentLoaded', async ()=>{
    bindChartTabs(); bindFilterBar(); applyCardFilters(); bindCardClickSync();
    const canvas = ensureChartCanvas();
    if(canvas){ const slug = activeSlug('urea'); await renderChart(canvas, slug, currentDays(), activeLabel()); canvas.__fertpCurrentSlug = slug; }
  });
})();