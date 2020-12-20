<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <title>Codigo de verificación</title>
</head>
<body>
    <center>
        <h1>Compra Realizada</h1>

        <p>Hola! Se ha creado una compra el {{ $verification->created_at }}.</p>
        <p>Estos son los datos de la compra:</p>
        <table border="1">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $verification->descripcion }}</td>
                    <td>{{ $verification->monto }}</td>
                </tr>
            </tbody>
        </table>
        <p>Y el Token de verificación es:</p>

        <h1>{{$verification->token}}</h1>

        <p>Debe Ingresar al siguiente enlace para poder ingresar el código</p>
        <a href="http://localhost:3001/verification/{{ $verification->session_id }}">
            Ingesar Codigo
        </a>
    </center>
</body>
</html>