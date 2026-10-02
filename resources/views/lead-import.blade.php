<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Імпорт заявок</title>
    <style>
        :root { color-scheme: light dark; font-family: system-ui, sans-serif; background: light-dark(#f4f6fa, #111827); color: light-dark(#172033, #e5e7eb); }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 48px 20px; }
        main { max-width: 640px; margin: auto; padding: 32px; border: 1px solid light-dark(#dce2ec, #374151); border-radius: 16px; background: light-dark(#fff, #1f2937); }
        h1 { margin: 0 0 12px; font-size: 28px; }
        p { line-height: 1.6; }
        .hint { color: light-dark(#526078, #aab4c4); font-size: 14px; }
        form { display: grid; gap: 16px; margin-top: 28px; }
        label { font-weight: 600; }
        input { width: 100%; padding: 14px; border: 1px dashed light-dark(#a3aec1, #6b7280); border-radius: 8px; font: inherit; }
        button { padding: 14px 20px; border: 0; border-radius: 8px; background: #2563eb; color: white; font: inherit; font-weight: 600; cursor: pointer; }
        button:disabled { opacity: .6; cursor: wait; }
        input:focus-visible, button:focus-visible { outline: 3px solid #93c5fd; outline-offset: 3px; }
        .notice { padding: 16px; margin-top: 24px; border-radius: 8px; overflow-wrap: anywhere; }
        .success { background: light-dark(#ecfdf5, #064e3b); }
        .error { background: light-dark(#fef2f2, #7f1d1d); }
        .notice p { margin: 4px 0; }
        #processing { margin: 0; }
        @media (max-width: 480px) { body { padding: 20px 12px; } main { padding: 24px 16px; } }
    </style>
</head>
<body>
<main>
    <h1>Імпорт заявок</h1>
    <p class="hint">Завантажте CSV у форматі leads.csv</p>

    @if (session('import'))
        <section class="notice success" role="status">
            <strong>Успішно</strong>
            <p>Файл: {{ session('import.filename') }}</p>
            <p>Записано рядків: <strong>{{ number_format(session('import.rows'), 0, '.', ' ') }}</strong></p>
            <p id="upload-time">Час імпорту: {{ number_format(session('import.seconds'), 3, '.', ' ') }} с</p>
        </section>
    @endif

    @if ($errors->any())
        <section class="notice error" role="alert" id="import-errors">
            <strong>Імпорт не виконано</strong>
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </section>
    @endif

    <form action="{{ route('leads.import') }}" method="post" enctype="multipart/form-data" id="import-form">
        @csrf
        <label for="file">CSV-файл із заявками</label>
        <input id="file" name="file" type="file" accept=".csv,text/csv" required @if ($errors->has('file')) aria-invalid="true" aria-describedby="import-errors" @endif>
        <p class="hint">Усі рядки додаються як окремі заявки, включно з повторними external_id. Повторне завантаження файлу додасть їх ще раз.</p>
        <button type="submit" id="import-button">Імпортувати</button>
        <p id="processing" class="hint" role="status" hidden>Файл завантажується та імпортується. Дочекайтеся результату…</p>
    </form>
</main>
<script>
    const form = document.getElementById('import-form');
    form.addEventListener('submit', () => {
        try { sessionStorage.setItem('lead-import-started', String(Date.now())); } catch {}
        document.getElementById('import-button').disabled = true;
        document.getElementById('processing').hidden = false;
        form.setAttribute('aria-busy', 'true');
    });
    try {
        const started = Number(sessionStorage.getItem('lead-import-started'));
        sessionStorage.removeItem('lead-import-started');
        const result = document.getElementById('upload-time');
        if (result && started > 0) {
            result.textContent = 'Час завантаження: ' + ((Date.now() - started) / 1000).toFixed(3) + ' с';
        }
    } catch {}
    window.addEventListener('pageshow', () => {
        document.getElementById('import-button').disabled = false;
        document.getElementById('processing').hidden = true;
        form.removeAttribute('aria-busy');
    });
</script>
</body>
</html>
