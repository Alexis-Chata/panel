<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Analítico {{ $session->title ?? $session->code }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h1,
        h2,
        h3 {
            margin: 0 0 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f2f2f2;
        }

        .small {
            color: #666;
            font-size: 11px;
        }

        .ok {
            color: #0a0;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h2>Analítico por pregunta — {{ $session->title ?? 'Partida' }} [{{ $session->code }}]</h2>
    <div class="small">
        Preguntas: {{ $session->questions_total }} ·
        Fecha: {{ now()->format('Y-m-d H:i') }}
    </div>
    <br>

    <table>
        <thead>
            <tr>
                <th style="width:28px">#</th>
                <th>Pregunta</th>
                <th style="width:90px">Correcta</th>
                <th style="width:90px">Respondidos</th>
                <th style="width:90px">Correctos</th>
                <th style="width:90px">% Acierto</th>
                <th style="width:180px">Distribución (A/B/C/D)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
                <tr>
                    <td>{{ $r['n'] }}</td>
                    <td>{{ $r['q'] }}</td>
                    <td><span class="ok">{{ $r['correct'] }}</span></td>
                    <td>{{ $r['answered'] }}</td>
                    <td>{{ $r['corrects'] }}</td>
                    <td>{{ number_format($r['acc'], 1) }}%</td>
                    <td>A: {{ $r['A'] }} · B: {{ $r['B'] }} · C: {{ $r['C'] }} · D:
                        {{ $r['D'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
