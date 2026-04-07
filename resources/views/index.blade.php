<?php

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Noticia;

$productos = Producto::with('categoria')->where('estado', 'DISPONIBLE')->orderBy('id', 'desc')->get();
$categorias = Categoria::all();
$promociones = Noticia::getPromocionesActivas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebCaps - Tienda Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .product-img {
            height: 250px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }
        .card-price {
            color: #2c3e50;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .btn-shop {
            background-color: #0c6e09;
            color: white;
        }
        .btn-shop:hover {
            background-color: #0a5507;
            color: white;
        }
        .promo-bar {
            background: linear-gradient(90deg, #ff6b6b, #ffa500);
            color: white;
            padding: 10px 0;
            overflow: hidden;
        }
        .promo-bar span {
            display: inline-block;
            white-space: nowrap;
            animation: marquee 15s linear infinite;
        }
        @keyframes marquee {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
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
        .cart-badge {
            font-size: 10px;
            min-width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/">
                <strong>WEBCAPS</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#productos">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/conocenos">Conócenos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="/carrito">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            <span x-show="carritoCantidad > 0" 
                                  x-text="carritoCantidad"
                                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge">
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Promo Bar -->
    @if(count($promociones) > 0)
    <div class="promo-bar">
        <div class="container">
            <span>
                @foreach($promociones as $promo)
                    {{ $promo }} &nbsp;&nbsp;|&nbsp;&nbsp; 
                @endforeach
            </span>
        </div>
    </div>
    @endif

    <!-- Carousel -->
    <div id="carouselExample" class="carousel slide" data-bs-ride="carousel" data-interval="6000">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="{{ asset('images/carrusel/6.jpg') }}" class="d-block w-100" alt="Promo 1" style="max-height: 400px; object-fit: cover;">
            </div>
            <div class="carousel-item">
                <img src="{{ asset('images/carrusel/4.jpg') }}" class="d-block w-100" alt="Promo 2" style="max-height: 400px; object-fit: cover;">
            </div>
            <div class="carousel-item">
                <img src="{{ asset('images/carrusel/1.jpg') }}" class="d-block w-100" alt="Promo 3" style="max-height: 400px; object-fit: cover;">
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
        </button>
    </div>

    <!-- Products Section -->
    <main class="container py-5" id="productos">
        <h2 class="text-center text-uppercase mb-4" style="letter-spacing: 5px;">Nuestros Productos</h2>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs justify-content-center mb-4" id="productTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                    General
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="basicas-tab" data-bs-toggle="tab" data-bs-target="#basicas" type="button">
                    Camisetas Básicas
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="productTabsContent">
            <!-- General Tab -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="row" x-data="productosData()">
                    @foreach($productos->where('categoria.categoria', '!=', 'BASICA') as $producto)
                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card product-card h-100">
                            @if($producto->foto && file_exists(public_path('storage/' . $producto->foto)))
                                <a href="/productos/{{ $producto->id }}">
                                    <img src="{{ asset('storage/' . $producto->foto) }}" class="card-img-top product-img" alt="{{ $producto->referencia }}">
                                </a>
                            @else
                                <a href="/productos/{{ $producto->id }}">
                                    <img src="{{ asset('images/sinfoto.jpg') }}" class="card-img-top product-img" alt="Sin foto">
                                </a>
                            @endif
                            <div class="card-body text-center">
                                <h5 class="card-title text-uppercase">{{ $producto->referencia }}</h5>
                                <p class="card-price">$ {{ number_format($producto->precio, 0, ',', '.') }} COP</p>
                                <button @click="agregarAlCarrito({{ $producto->id }}, '{{ $producto->tallas ? explode(',', $producto->tallas)[0] : '' }}')" 
                                        class="btn btn-shop w-100">
                                    <i class="fas fa-cart-plus"></i> Agregar
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    @if($productos->where('categoria.categoria', '!=', 'BASICA')->count() == 0)
                    <div class="col-12 text-center">
                        <h4>Actualmente no hay productos disponibles</h4>
                        <p>Estate pendiente de nuestras próximas colecciones!</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Básicas Tab -->
            <div class="tab-pane fade" id="basicas" role="tabpanel">
                <div class="row">
                    @foreach($productos->where('categoria.categoria', 'BASICA') as $producto)
                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card product-card h-100">
                            @if($producto->foto && file_exists(public_path('storage/' . $producto->foto)))
                                <a href="/productos/{{ $producto->id }}">
                                    <img src="{{ asset('storage/' . $producto->foto) }}" class="card-img-top product-img" alt="{{ $producto->referencia }}">
                                </a>
                            @else
                                <a href="/productos/{{ $producto->id }}">
                                    <img src="{{ asset('images/sinfoto.jpg') }}" class="card-img-top product-img" alt="Sin foto">
                                </a>
                            @endif
                            <div class="card-body text-center">
                                <h5 class="card-title text-uppercase">{{ $producto->referencia }}</h5>
                                <p class="card-price">$ {{ number_format($producto->precio, 0, ',', '.') }} COP</p>
                                <a href="/productos/{{ $producto->id }}" class="btn btn-shop w-100">
                                    <i class="fas fa-eye"></i> Ver Detalle
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    @if($productos->where('categoria.categoria', 'BASICA')->count() == 0)
                    <div class="col-12 text-center">
                        <h4>Actualmente no hay productos disponibles</h4>
                        <p>Estate pendiente de nuestras próximas colecciones!</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <!-- WhatsApp Float -->
    <a href="https://wa.me/573000000000" class="btn-whatsapp" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; {{ date('Y') }} WebCaps. Todos los derechos reservados.</p>
            <div class="social-links">
                <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                <a href="#" class="text-white"><i class="fab fa-tiktok fa-lg"></i></a>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Alpine.js Global Cart State
        document.addEventListener('alpine:init', () => {
            Alpine.store('carrito', {
                cantidad: 0,
                total: 0,
                
                actualizar(datos) {
                    this.cantidad = datos.cantidad_total || 0;
                    this.total = datos.total || 0;
                }
            });
        });

        function productosData() {
            return {
                async agregarAlCarrito(productoId, talla) {
                    try {
                        const response = await fetch('/api/carrito/agregar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                producto_id: productoId,
                                cantidad: 1,
                                talla: talla
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Actualizar estado global del carrito
                            this.$store.carrito.actualizar(data.data.carrito);
                            
                            Swal.fire({
                                icon: 'success',
                                title: '¡Agregado!',
                                text: 'Producto agregado al carrito',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo agregar el producto'
                        });
                    }
                }
            };
        }
    </script>
</body>
</html>