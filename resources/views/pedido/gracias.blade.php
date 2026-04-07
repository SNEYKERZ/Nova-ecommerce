<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - WebCaps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .success-icon { font-size: 4rem; color: #28a745; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/"><strong>WEBCAPS</strong></a>
        </div>
    </nav>
    <div class="container py-5 text-center">
        <div class="success-icon mb-4">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="text-success">¡Gracias por tu compra!</h1>
        <p class="lead">Tu pedido #{{ $pedidoId }} ha sido confirmado exitosamente.</p>
        <p>Recibirás un correo electrónico con los detalles de tu pedido.</p>
        <a href="/" class="btn btn-primary btn-lg mt-3">Seguir Comprando</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>