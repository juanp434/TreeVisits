<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TreeVisits — Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            margin: 0; padding: 2rem; background: #0f172a; color: #e2e8f0;
        }
        h1 { margin: 0 0 .25rem; font-size: 1.6rem; }
        p.subtitle { margin: 0 0 1.5rem; color: #94a3b8; }
        .cards { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem; }
        .card {
            background: #1e293b; border-radius: .75rem; padding: 1.25rem 1.5rem;
            min-width: 160px; flex: 1;
        }
        .card .label { color: #94a3b8; font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; }
        .card .value { font-size: 2rem; font-weight: 700; margin-top: .25rem; }
        .panel { background: #1e293b; border-radius: .75rem; padding: 1.5rem; margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .5rem .75rem; border-bottom: 1px solid #334155; }
        th { color: #94a3b8; font-weight: 600; }
        .empty { color: #94a3b8; padding: 1rem 0; }
    </style>
</head>
<body>
    <h1>🌳 TreeVisits</h1>
    <p class="subtitle">Visits aggregated per hour · {{ $visitsPerTree }} visits = 1 tree</p>

    <div class="cards">
        <div class="card">
            <div class="label">Total visits</div>
            <div class="value">{{ $totalVisits }}</div>
        </div>
        <div class="card">
            <div class="label">Trees planted</div>
            <div class="value">{{ $totalTrees }}</div>
        </div>
    </div>

    <div class="panel">
        <canvas id="chart" height="100"></canvas>
    </div>

    <div class="panel">
        <table>
            <thead><tr><th>Hour</th><th>Visits</th></tr></thead>
            <tbody id="rows"></tbody>
        </table>
        <div id="empty" class="empty" hidden>No visits recorded yet.</div>
    </div>

    <script>
        async function load() {
            const res = await fetch('/api/visits/hourly');
            const { data } = await res.json();

            const tbody = document.getElementById('rows');
            const empty = document.getElementById('empty');
            tbody.innerHTML = '';
            empty.hidden = data.length > 0;

            for (const row of data) {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${row.hour}</td><td>${row.visits}</td>`;
                tbody.appendChild(tr);
            }

            new Chart(document.getElementById('chart'), {
                type: 'bar',
                data: {
                    labels: data.map(r => r.hour),
                    datasets: [{
                        label: 'Visits',
                        data: data.map(r => r.visits),
                        backgroundColor: '#22c55e',
                        borderRadius: 4,
                    }],
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: '#94a3b8' }, grid: { display: false } },
                        y: { beginAtZero: true, ticks: { color: '#94a3b8', precision: 0 }, grid: { color: '#334155' } },
                    },
                },
            });
        }
        load();
    </script>
</body>
</html>
