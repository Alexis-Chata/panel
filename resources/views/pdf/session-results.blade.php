<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Resultados {{ $session->title ?? $session->code }}</title>
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
        }

        th {
            background: #f2f2f2;
        }

        .small {
            color: #666;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <h2>Resultados — {{ $session->title ?? 'Partida' }} [{{ $session->code }}]</h2>
    <div class="small">Preguntas: {{ $session->questions_total }} — Fecha: {{ now()->format('Y-m-d H:i') }}</div>
    <br>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Participante</th>
                <th>Email</th>
                <th>Puntaje</th>
                <th>Tiempo (s)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ranking as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->nickname ?? $p->user?->name }}</td>
                    <td>{{ $p->user?->email }}</td>
                    <td>{{ $p->score }}</td>
                    <td>{{ number_format($p->time_total_ms / 1000, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
