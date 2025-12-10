<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Resultados</title>

    <!-- Fuente Confortaa desde Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@400;600&display=swap" rel="stylesheet">

    <style>
        /* ====== TIPOGRAFÍA Y LAYOUT ====== */
        body {
            font-family: 'Comfortaa';
            font-size: 13px;
            color: #2c3e50;
            margin: 30px;
            background-color: #f9f9f9;
        }

        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 5px;
            color: #34495e;
        }
  h2 {
            text-align: center;
            
            font-weight: 400;
            margin-top: 0;
            color: #7f8c8d;
        }
        h3 {
            text-align: center;
            font-size: 14px;
            font-weight: 400;
            margin-top: 0;
            color: #7f8c8d;
        }

        /* ====== TABLA ====== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            background-color: #ffffff;
        }

        thead th {
            background: linear-gradient(to bottom, #ecf0f1, #bdc3c7);
            color: #2c3e50;
            font-weight: 600;
            padding: 10px 8px;
            border-bottom: 2px solid #95a5a6;
            text-align: left;
            font-size: 13px;
        }

        tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 13px;
        }

        tbody tr:nth-child(odd) {
            background-color: #f7f9fa;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Hover para filas */
        tbody tr:hover {
            background-color: #dce6f0;
            transition: background-color 0.3s ease;
        }
    </style>
</head>
<body>

    <h1>Resultados de las Olimpiadas Científicas</h1>
    <h2>Fase {{ $fase ?? 'Final' }}</h2>
    <h3>Generado el {{ $fecha ?? now()->format('d/m/Y') }}</h3>

    <table>
        <thead>
            <tr>
                @foreach ($headings as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @foreach ($rows as $fila)
                <tr>
                    @foreach ($fila as $celda)
                        <td>{{ $celda }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
