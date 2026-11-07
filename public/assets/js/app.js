document.querySelectorAll('[data-toggle-password]').forEach(button => button.addEventListener('click', () => {
  const field = document.getElementById(button.dataset.togglePassword);
  field.type = field.type === 'password' ? 'text' : 'password';
}));
if ('serviceWorker' in navigator && window.isSecureContext) {
  window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
document.querySelector('[data-menu-toggle]')?.addEventListener('click', () => document.body.classList.toggle('menu-open'));
document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
  const root = document.documentElement;
  const theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
  root.dataset.theme = theme;
  const body = new URLSearchParams({ _csrf: document.querySelector('meta[name="csrf-token"]').content, theme });
  fetch('/preferences/theme', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body }).catch(() => {});
});
const transactionForm = document.querySelector('#transaction-form form');
const typeField = transactionForm?.querySelector('select[name="type"]');
const categoryField = transactionForm?.querySelector('select[name="category_id"]');
const statusField = transactionForm?.querySelector('select[name="status"]');
function syncTransactionOptions() {
  if (!typeField) return;
  const type = typeField.value;
  categoryField?.querySelectorAll('option[data-type]').forEach(option => { option.hidden = option.dataset.type !== type; });
  if (categoryField?.selectedOptions[0]?.hidden) categoryField.value = '';
  if (statusField) {
    statusField.querySelectorAll('option').forEach(option => {
      const allowed = option.value === 'pending' || (type === 'income' && option.value === 'received') || (type === 'expense' && option.value === 'paid');
      option.hidden = !allowed;
      option.disabled = !allowed;
    });
    if (statusField.selectedOptions[0]?.disabled) statusField.value = 'pending';
  }
}
typeField?.addEventListener('change', syncTransactionOptions);
syncTransactionOptions();
const recurrenceForm = document.querySelector('form[action="/recurrences/save"]');
const recurrenceType = recurrenceForm?.querySelector('select[name="type"]');
const recurrenceCategory = recurrenceForm?.querySelector('select[name="category_id"]');
recurrenceType?.addEventListener('change', () => {
  recurrenceCategory?.querySelectorAll('option[data-type]').forEach(option => { option.hidden = option.dataset.type !== recurrenceType.value; });
  if (recurrenceCategory?.selectedOptions[0]?.hidden) recurrenceCategory.value = '';
});
recurrenceType?.dispatchEvent(new Event('change'));

const dashboardFilter = document.querySelector('[data-dashboard-filter]');
const periodSelect = dashboardFilter?.querySelector('[data-period-select]');
const customRange = dashboardFilter?.querySelector('[data-custom-range]');
periodSelect?.addEventListener('change', () => {
  if (customRange) customRange.hidden = periodSelect.value !== 'custom';
});

const chartDataNode = document.getElementById('dashboard-chart-data');
if (chartDataNode && window.Chart) {
  const chartData = JSON.parse(chartDataNode.textContent);
  const moneyLabel = value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
  const ink = getComputedStyle(document.documentElement).getPropertyValue('--muted').trim() || '#818b9b';
  const grid = getComputedStyle(document.documentElement).getPropertyValue('--line').trim() || '#e9edf3';
  const common = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: { legend: { position: 'bottom', labels: { color: ink, usePointStyle: true, boxWidth: 7, padding: 16, font: { size: 10 } } }, tooltip: { callbacks: { label: item => `${item.dataset.label || item.label}: ${moneyLabel(item.raw)}` } } },
    scales: { x: { grid: { display: false }, ticks: { color: ink, font: { size: 9 }, maxRotation: 0 } }, y: { beginAtZero: true, grid: { color: grid }, ticks: { color: ink, font: { size: 9 }, callback: value => moneyLabel(value) } } }
  };
  const makeChart = (id, type, labels, datasets, options = {}) => {
    const canvas = document.getElementById(id);
    if (canvas) new Chart(canvas, { type, data: { labels, datasets }, options: { ...common, ...options } });
  };
  makeChart('cashflow-chart', 'bar', chartData.months.map(point => point.label), [
    { label: 'Receitas', data: chartData.months.map(point => point.income / 100), backgroundColor: '#55b994', borderRadius: 5 },
    { label: 'Despesas', data: chartData.months.map(point => point.expense / 100), backgroundColor: '#e78c7c', borderRadius: 5 }
  ]);
  makeChart('balance-chart', 'line', chartData.balanceLabels, [{ label: 'Saldo', data: chartData.balanceValues.map(value => value / 100), borderColor: '#4d86ca', backgroundColor: '#4d86ca20', fill: true, tension: .3, pointRadius: 3 }]);
  makeChart('category-chart', 'doughnut', chartData.categoryLabels, [{ label: 'Despesas', data: chartData.categoryValues.map(value => value / 100), backgroundColor: ['#168b68','#5478d4','#d7854d','#8b72c3','#d46d75','#548caa','#a077bb'], borderWidth: 0 }], { cutout: '66%', scales: {}, plugins: { ...common.plugins, tooltip: { callbacks: { label: item => `${item.label}: ${moneyLabel(item.raw)}` } } } });
  makeChart('weekly-chart', 'bar', chartData.weekLabels, [{ label: 'Despesas', data: chartData.weekValues.map(value => value / 100), backgroundColor: '#e78c7c', borderRadius: 5 }]);
} else if (chartDataNode) {
  document.querySelectorAll('.canvas-box, .donut-box').forEach(box => { box.textContent = 'Conecte-se à internet para carregar os gráficos.'; box.classList.add('chart-offline'); });
}
