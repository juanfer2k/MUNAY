<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Fetch API</title>
</head>
<body>
    <h1>Test de Fetch a API</h1>
    <button onclick="testUsers()">Cargar Usuarios</button>
    <pre id="resultado">Esperando...</pre>

    <script>
    function testUsers() {
        document.getElementById('resultado').textContent = 'Cargando...';
        fetch('api/users.php')
            .then(res => res.json())
            .then(data => {
                document.getElementById('resultado').textContent = JSON.stringify(data, null, 2);
            })
            .catch(err => {
                document.getElementById('resultado').textContent = 'Error: ' + err.message;
            });
    }
    </script>
</body>
</html>