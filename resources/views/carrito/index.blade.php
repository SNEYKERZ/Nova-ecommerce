<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - WebCaps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .table { background: white; border-radius: 8px; overflow: hidden; }
        .table thead { background: #343a40; color: white; }
        .total-section { background: #f8f9fa; padding: 20px; border-radius: 8px; }
        .btn-whatsapp {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #25d366;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.3);
            z-index: 1000;
            text-decoration: none;
        }
    </style>
</head>
<body x-data="carritoApp()" x-init="initCarrito()">
    
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <strong>WEBCAPS</strong>
            </a>
            <a href="/" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Seguir Comprando
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <h2 class="text-center text-uppercase mb-4">Carrito de Compras</h2>
        <p class="text-center text-muted">Los cambios se actualizan en tiempo real</p>

        <!-- Loading -->
        <div x-show="cargando" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>

        <!-- Carrito Vacío -->
        <div x-show="!cargando && items.length === 0" class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h4>No hay productos en el carrito</h4>
            <a href="/" class="btn btn-primary mt-3">Ver Productos</a>
        </div>

        <!-- Carrito con Items -->
        <div x-show="!cargando && items.length > 0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Referencia</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Talla</th>
                            <th>Subtotal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in items" :key="item.id">
                            <tr>
                                <td>
                                    <img :src="item.producto.foto ? '/storage/' + item.producto.foto : '/images/sinfoto.jpg'" 
                                         class="product-img" alt="Producto">
                                </td>
                                <td x-text="item.producto.referencia"></td>
                                <td>$ <span x-text="formatearNumero(item.producto.precio)"></span> COP</td>
                                <td>
                                    <input type="number" 
                                           :value="item.cantidad" 
                                           @change="actualizarCantidad(item.id, $event.target.value)"
                                           min="1" 
                                           class="form-control" 
                                           style="width: 80px;">
                                </td>
                                <td>
                                    <select @change="actualizarTalla(item.id, $event.target.value)" 
                                            class="form-select" 
                                            style="width: 100px;">
                                        <option value="">Seleccionar</option>
                                        <template x-if="item.producto.tallas">
                                            <template x-for="talla in item.producto.tallas.split(',')">
                                                <option :value="talla" :selected="talla === item.talla" x-text="talla"></option>
                                            </template>
                                        </template>
                                    </select>
                                </td>
                                <td>$ <span x-text="formatearNumero(item.subtotal)"></span> COP</td>
                                <td>
                                    <button @click="eliminarItem(item.id)" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end"><strong>Total:</strong></td>
                            <td><strong>$ <span x-text="formatearNumero(total)"></span> COP</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Actions -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <button @click="vaciarCarrito()" class="btn btn-outline-danger">
                        <i class="fas fa-trash"></i> Vaciar Carrito
                    </button>
                </div>
                <div class="col-md-6 text-md-end">
                    <button @click="finalizarCompra()" class="btn btn-success btn-lg">
                        <i class="fas fa-check"></i> Finalizar Compra
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Checkout -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Datos para Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="checkoutForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellidos</label>
                                <input type="text" class="form-control" name="apellidos">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dirección (Ciudad, Barrio, Dirección completa)</label>
                            <textarea class="form-control" name="direccion" rows="2" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" @click="confirmarPedido()" class="btn btn-primary">Confirmar Pedido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-3 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; {{ date('Y') }} WebCaps. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@latest/dist/echo.min.js"></script>
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    
    <script>
        function carritoApp() {
            return {
                items: [],
                total: 0,
                cantidadTotal: 0,
                cargando: true,
                checkoutModal: null,

                async initCarrito() {
                    this.cargando = true;
                    await this.cargarCarrito();
                    this.configurarBroadcasting();
                    
                    // Inicializar modal
                    this.checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
                },

                async cargarCarrito() {
                    try {
                        const response = await fetch('/api/carrito');
                        const data = await response.json();
                        
                        if (data.success) {
                            this.items = data.data.items;
                            this.total = data.data.total;
                            this.cantidadTotal = data.data.cantidad_total;
                        }
                    } catch (error) {
                        console.error('Error cargando carrito:', error);
                    } finally {
                        this.cargando = false;
                    }
                },

                configurarBroadcasting() {
                    // Configurar Laravel Echo para WebSocket
                    if (typeof Pusher !== 'undefined') {
                        window.Echo = new Echo({
                            broadcaster: 'pusher',
                            key: '{{ config("broadcasting.connections.pusher.key") }}',
                            cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}',
                            forceTLS: true
                        });

                        // Escuchar eventos del carrito
                        window.Echo.channel('carrito')
                            .listen('CarritoActualizado', (event) => {
                                this.items = event.carrito.items;
                                this.total = event.carrito.total;
                                this.cantidadTotal = event.carrito.cantidad_total;
                            });
                    }
                },

                async actualizarCantidad(itemId, cantidad) {
                    try {
                        const response = await fetch(`/api/carrito/actualizar/${itemId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ cantidad: parseInt(cantidad) })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            this.items = data.data.items;
                            this.total = data.data.total;
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },

                async actualizarTalla(itemId, talla) {
                    try {
                        const response = await fetch(`/api/carrito/actualizar/${itemId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ talla: talla })
                        });
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },

                async eliminarItem(itemId) {
                    try {
                        const response = await fetch(`/api/carrito/eliminar/${itemId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            this.items = data.data.items;
                            this.total = data.data.total;
                            this.cantidadTotal = data.data.cantidad_total;
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },

                async vaciarCarrito() {
                    const result = await Swal.fire({
                        title: '¿Vaciar carrito?',
                        text: 'Se eliminarán todos los productos',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, vaciar',
                        cancelButtonText: 'Cancelar'
                    });

                    if (result.isConfirmed) {
                        try {
                            await fetch('/api/carrito/vaciar', {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            });
                            
                            this.items = [];
                            this.total = 0;
                            this.cantidadTotal = 0;
                        } catch (error) {
                            console.error('Error:', error);
                        }
                    }
                },

                finalizarCompra() {
                    this.checkoutModal.show();
                },

                async confirmarPedido() {
                    const form = document.getElementById('checkoutForm');
                    const formData = new FormData(form);
                    
                    const datos = {
                        nombre: formData.get('nombre'),
                        apellidos: formData.get('apellidos'),
                        email: formData.get('email'),
                        telefono: formData.get('telefono'),
                        direccion: formData.get('direccion'),
                        items: this.items.map(item => ({
                            producto_id: item.producto.id,
                            cantidad: item.cantidad,
                            talla: item.talla
                        }))
                    };

                    try {
                        const response = await fetch('/api/pedidos', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(datos)
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.checkoutModal.hide();
                            await Swal.fire({
                                icon: 'success',
                                title: '¡Pedido Confirmado!',
                                text: 'Tu pedido ha sido registrado correctamente',
                                confirmButtonText: 'Aceptar'
                            });
                            
                            // Limpiar carrito
                            this.items = [];
                            this.total = 0;
                            this.cantidadTotal = 0;
                            
                            // Redirect a gracias
                            window.location.href = '/pedido/gracias/' + data.data.id;
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo crear el pedido'
                            });
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error al procesar el pedido'
                        });
                    }
                },

                formatearNumero(numero) {
                    return new Intl.NumberFormat('es-CO').format(numero);
                }
            };
        }
    </script>
</body>
</html>