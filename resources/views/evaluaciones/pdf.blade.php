<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lista de Evaluaciones</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
        th { background-color: #0284C7; color: #fff; }
    </style>
</head>
<body>
    <h2>Lista Oficial de Evaluaciones - Fase Final</h2>
    <p>Área: {{ $area ?? 'Todas' }}, Nivel: {{ $nivel ?? 'Todos' }}</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>CI</th>
                <th>Nombre</th>
                <th>Área</th>
                <th>Nivel</th>
                <th>Curso/Grado</th>
                <th>Nota</th>
                <th>Clasificación</th>
                <th>Fase</th>
            </tr>
        </thead>
        <tbody>
            @foreach($evaluaciones as $eva)
            <tr>
                <td>{{ $eva->id_inscrito }}</td>
                <td>{{ $eva->ci }}</td>
                <td>{{ $eva->nombre }}</td>
                <td>{{ $eva->area }}</td>
                <td>{{ $eva->nivel }}</td>
                <td>{{ $eva->grado }}</td>
                <td>{{ $eva->nota }}</td>
                <td>{{ $eva->estado_clasificado }}</td>
                <td>{{ $eva->fase }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
