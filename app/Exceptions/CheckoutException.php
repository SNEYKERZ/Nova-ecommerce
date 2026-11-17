<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error de negocio durante el checkout o la gestión de inventario
 * (stock insuficiente, producto no disponible, talla inválida...).
 *
 * Se renderiza como JSON 422 {success: false, message} (ver bootstrap/app.php).
 */
class CheckoutException extends RuntimeException {}
