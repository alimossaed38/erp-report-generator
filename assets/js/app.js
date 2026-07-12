document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('canvas[data-values]').forEach(function (cv) {
    var data;
    try { data = JSON.parse(cv.getAttribute('data-values')); } catch (e) { return; }
    var type = cv.getAttribute('data-type') || 'bar';
    var labels = data.map(function (d) { return d.label; });
    var values = data.map(function (d) { return d.value; });
    var colors = ['#2563eb','#0f766e','#d97706','#dc2626','#7c3aed','#16a34a'];
    new Chart(cv, {
      type: type,
      data: {
        labels: labels,
        datasets: (data[0] && data[0].series) ? buildSeries(data) : [{
          label: cv.getAttribute('data-label') || '',
          data: values,
          backgroundColor: type === 'line' ? 'rgba(37,99,235,.15)' : colors,
          borderColor: '#2563eb',
          borderWidth: 2, tension: .3, fill: type === 'line'
        }]
      },
      options: {
        responsive: true, plugins: { legend: { display: type !== 'bar' || !!(data[0] && data[0].series) } },
        scales: { y: { beginAtZero: true } }
      }
    });
  });
  function buildSeries(data){
    var keys = Object.keys(data[0].series);
    var palette = { income:'#16a34a', expense:'#dc2626' };
    return keys.map(function(k,i){
      return { label: k==='income'?'إيراد':(k==='expense'?'مصروف':k),
        data: data.map(function(d){return d.series[k];}),
        backgroundColor: palette[k] || ['#2563eb','#d97706'][i%2] };
    });
  }
});
