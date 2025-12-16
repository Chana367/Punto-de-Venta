<?php
require_once "../conexion.php";
session_start();

if (isset($_GET['q'])) {
    $datos = array();
    $nombre = $_GET['q'];

    // Consulta para buscar clientes por nombre
    $cliente = $conexion->prepare("SELECT * FROM cliente WHERE nombre LIKE :nombre");
    $nombre = "%" . $nombre . "%";
    $cliente->bindParam(':nombre', $nombre, PDO::PARAM_STR);
    $cliente->execute();

    while ($row = $cliente->fetch(PDO::FETCH_ASSOC)) {
        $data['id'] = $row['idcliente'];
        $data['label'] = $row['nombre'];
        $data['direccion'] = $row['direccion'];
        $data['telefono'] = $row['telefono'];
        array_push($datos, $data);
    }
    echo json_encode($datos);
    die();
} elseif (isset($_GET['pro'])) {
    $datos = array();
    $nombre = $_GET['pro'];

    // Consulta para buscar productos por código o descripción (solo activos)
    $producto = $conexion->prepare("SELECT * FROM producto WHERE (codigo LIKE :nombre OR descripcion LIKE :nombre) AND activo = 1");
    $nombre = "%" . $nombre . "%";
    $producto->bindParam(':nombre', $nombre, PDO::PARAM_STR);
    $producto->execute();

    while ($row = $producto->fetch(PDO::FETCH_ASSOC)) {
        $data['id'] = $row['codproducto'];
        $data['label'] = $row['codigo'] . ' - ' . $row['descripcion'];
        $data['value'] = $row['codigo'] . ' - ' . $row['descripcion'];
        $data['codigo'] = $row['codigo'];
        $data['descripcion'] = $row['descripcion'];
        $data['precio'] = $row['precio'];
        $data['cantidad'] = $row['cantidad'];
        array_push($datos, $data);
    }
    echo json_encode($datos);
    die();
} elseif (isset($_GET['detalle'])) {
    $id = $_SESSION['idUser'];
    $datos = array();

    // Consulta para obtener el detalle temporal del usuario
    $detalle = $conexion->prepare("SELECT d.*, p.codproducto, p.descripcion FROM detalle_temp d INNER JOIN producto p ON d.id_producto = p.codproducto WHERE d.id_usuario = :id");
    $detalle->bindParam(':id', $id, PDO::PARAM_INT);
    $detalle->execute();

    while ($row = $detalle->fetch(PDO::FETCH_ASSOC)) {
        $data['id'] = $row['id'];
        $data['descripcion'] = $row['descripcion'];
        $data['cantidad'] = $row['cantidad'];
        $data['precio_venta'] = $row['precio_venta'];
        $data['sub_total'] = $row['total'];
        array_push($datos, $data);
    }
    echo json_encode($datos);
    die();
} elseif (isset($_GET['delete_detalle'])) {
    $id_detalle = $_GET['id'];

    // Consulta para eliminar detalle temporal
    $query = $conexion->prepare("DELETE FROM detalle_temp WHERE id = :id_detalle");
    $query->bindParam(':id_detalle', $id_detalle, PDO::PARAM_INT);
    $query->execute();

    echo $query ? "ok" : "Error";
    die();
} elseif (isset($_GET['procesarVenta'])) {
    $id_cliente = $_GET['id'];
    $id_user = $_SESSION['idUser'];
    $metodo_pago = isset($_GET['metodo_pago']) ? $_GET['metodo_pago'] : 'efectivo';
    $monto_pagado = isset($_GET['monto_pagado']) ? floatval($_GET['monto_pagado']) : 0;
    $turno = isset($_GET['turno']) ? $_GET['turno'] : '';
    $descuento_global = isset($_GET['descuento_global']) ? floatval($_GET['descuento_global']) : 0;

    // Validar que existan productos en el carrito
    $checkDetalle = $conexion->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) AS total_pagar FROM detalle_temp WHERE id_usuario = :id_user");
    $checkDetalle->bindParam(':id_user', $id_user, PDO::PARAM_INT);
    $checkDetalle->execute();
    $result = $checkDetalle->fetch(PDO::FETCH_ASSOC);
    $detalleCount = intval($result['count']);
    $total_sin_descuento = floatval($result['total_pagar']);
    
    // Validar que hay productos y el total no sea 0
    if ($detalleCount == 0) {
        $msg = array('mensaje' => 'error', 'detalle' => 'No hay productos en el carrito');
        echo json_encode($msg);
        die();
    }
    
    if ($total_sin_descuento <= 0) {
        $msg = array('mensaje' => 'error', 'detalle' => 'El total de la venta debe ser mayor a $0');
        echo json_encode($msg);
        die();
    }
    
    // Aplicar descuento global
    $total = $total_sin_descuento * (1 - $descuento_global / 100);

    // Calcular vuelto
    $vuelto = $monto_pagado - $total;
    if ($vuelto < 0) {
        $vuelto = 0; // Si el monto pagado es menor al total, no hay vuelto
    }

    // Insertar venta con los nuevos campos incluyendo descuento_global
    $insertar = $conexion->prepare("INSERT INTO ventas (id_cliente, total, id_usuario, metodo_pago, monto_pagado, vuelto, turno, descuento_global) VALUES (:id_cliente, :total, :id_user, :metodo_pago, :monto_pagado, :vuelto, :turno, :descuento)");
    $insertar->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
    $insertar->bindParam(':total', $total, PDO::PARAM_STR);
    $insertar->bindParam(':id_user', $id_user, PDO::PARAM_INT);
    $insertar->bindParam(':metodo_pago', $metodo_pago, PDO::PARAM_STR);
    $insertar->bindParam(':monto_pagado', $monto_pagado, PDO::PARAM_STR);
    $insertar->bindParam(':vuelto', $vuelto, PDO::PARAM_STR);
    $insertar->bindParam(':turno', $turno, PDO::PARAM_STR);
    $insertar->bindParam(':descuento', $descuento_global, PDO::PARAM_STR);
    $insertar->execute();

    if ($insertar) {
        // Obtener el último ID de la venta insertada
        $id_maximo = $conexion->query("SELECT MAX(id) AS total FROM ventas");
        $resultId = $id_maximo->fetch(PDO::FETCH_ASSOC);
        $ultimoId = $resultId['total'];

        // Obtener el detalle temporal del usuario
        $consultaDetalle = $conexion->prepare("SELECT * FROM detalle_temp WHERE id_usuario = :id_user");
        $consultaDetalle->bindParam(':id_user', $id_user, PDO::PARAM_INT);
        $consultaDetalle->execute();

        while ($row = $consultaDetalle->fetch(PDO::FETCH_ASSOC)) {
            $id_producto = $row['id_producto'];
            $cantidad = $row['cantidad'];
            $precio = $row['precio_venta'];
            $total_item = $row['total'];
            $desc = 0; // detalle_temp no tiene columna descuento individual

            // Insertar detalle de venta
            $insertarDet = $conexion->prepare("INSERT INTO detalle_venta (id_producto, id_venta, cantidad, precio, descuento, total) VALUES (:id_producto, :id_venta, :cantidad, :precio, :descuento, :total)");
            $insertarDet->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $insertarDet->bindParam(':id_venta', $ultimoId, PDO::PARAM_INT);
            $insertarDet->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $insertarDet->bindParam(':precio', $precio, PDO::PARAM_STR);
            $insertarDet->bindParam(':descuento', $desc, PDO::PARAM_STR);
            $insertarDet->bindParam(':total', $total_item, PDO::PARAM_STR);
            $insertarDet->execute();

            // Actualizar stock del producto
            $stockActual = $conexion->prepare("SELECT cantidad FROM producto WHERE codproducto = :id_producto");
            $stockActual->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stockActual->execute();
            $stockNuevo = $stockActual->fetch(PDO::FETCH_ASSOC);
            $stockTotal = $stockNuevo['cantidad'] - $cantidad;

            $stock = $conexion->prepare("UPDATE producto SET cantidad = :stockTotal WHERE codproducto = :id_producto");
            $stock->bindParam(':stockTotal', $stockTotal, PDO::PARAM_INT);
            $stock->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stock->execute();
        }

        // Eliminar detalle temporal del usuario
        $eliminar = $conexion->prepare("DELETE FROM detalle_temp WHERE id_usuario = :id_user");
        $eliminar->bindParam(':id_user', $id_user, PDO::PARAM_INT);
        $eliminar->execute();

        $msg = array('id_cliente' => $id_cliente, 'id_venta' => $ultimoId);
    } else {
        $msg = array('mensaje' => 'error');
    }

    echo json_encode($msg);
    die();
} elseif (isset($_GET['editarCliente'])) {
    $idcliente = $_GET['id'];

    // Consulta para obtener datos del cliente
    $sql = $conexion->prepare("SELECT * FROM cliente WHERE idcliente = :idcliente");
    $sql->bindParam(':idcliente', $idcliente, PDO::PARAM_INT);
    $sql->execute();

    $data = $sql->fetch(PDO::FETCH_ASSOC);
    echo json_encode($data);
    exit();

} elseif (isset($_GET['editarUsuario'])) {
    $idusuario = $_GET['id'];

    // Consulta para obtener datos del usuario
    $sql = $conexion->prepare("SELECT * FROM usuario WHERE idusuario = :idusuario");
    $sql->bindParam(':idusuario', $idusuario, PDO::PARAM_INT);
    $sql->execute();

    $data = $sql->fetch(PDO::FETCH_ASSOC);
    echo json_encode($data);
    exit();
} elseif (isset($_GET['editarProducto'])) {
    $id = $_GET['id'];

    // Consulta para obtener datos del producto
    $sql = $conexion->prepare("SELECT * FROM producto WHERE codproducto = :id");
    $sql->bindParam(':id', $id, PDO::PARAM_INT);
    $sql->execute();

    $data = $sql->fetch(PDO::FETCH_ASSOC);
    echo json_encode($data);
    exit();
}
if (isset($_POST['regDetalle'])) {
    $id = $_POST['id'];
    $cant = $_POST['cant'];
    $precio = $_POST['precio'];
    $id_user = $_SESSION['idUser'];
    $total = $precio * $cant;

    // Verificar existencia del producto
    $verificarExistencia = $conexion->prepare("SELECT cantidad FROM producto WHERE codproducto = :id");
    $verificarExistencia->bindParam(':id', $id, PDO::PARAM_INT);
    $verificarExistencia->execute();
    $existencia = $verificarExistencia->fetch(PDO::FETCH_ASSOC);

    if (!$existencia || $existencia['cantidad'] <= 0) {
        $msg = "Error: No hay stock disponible para este producto.";
    } else {
        $verificar = $conexion->prepare("SELECT * FROM detalle_temp WHERE id_producto = :id AND id_usuario = :id_user");
        $verificar->bindParam(':id', $id, PDO::PARAM_INT);
        $verificar->bindParam(':id_user', $id_user, PDO::PARAM_INT);
        $verificar->execute();
        $result = $verificar->rowCount();
        $datos = $verificar->fetch(PDO::FETCH_ASSOC);

        $cantidadSolicitada = $cant;
        if ($result > 0) {
            // Si ya existe en el carrito, sumar las cantidades
            $cantidadSolicitada += $datos['cantidad'];
        }

        if ($cantidadSolicitada > $existencia['cantidad']) {
            $msg = "Error: La cantidad solicitada ($cantidadSolicitada) supera el stock disponible ({$existencia['cantidad']}).";
        } else {
            if ($result > 0) {
                // Actualizar cantidad y total en detalle_temp
                $total_precio = ($cantidadSolicitada * $precio);
                $query = $conexion->prepare("UPDATE detalle_temp SET cantidad = :cantidadSolicitada, total = :total_precio WHERE id_producto = :id AND id_usuario = :id_user");
                $query->bindParam(':cantidadSolicitada', $cantidadSolicitada, PDO::PARAM_INT);
                $query->bindParam(':total_precio', $total_precio, PDO::PARAM_STR);
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->bindParam(':id_user', $id_user, PDO::PARAM_INT);
                $query->execute();

                $msg = $query ? "actualizado" : "Error al ingresar";
            } else {
                // Insertar en detalle_temp
                $query = $conexion->prepare("INSERT INTO detalle_temp (id_usuario, id_producto, cantidad, precio_venta, total) VALUES (:id_user, :id, :cant, :precio, :total)");
                $query->bindParam(':id_user', $id_user, PDO::PARAM_INT);
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->bindParam(':cant', $cant, PDO::PARAM_INT);
                $query->bindParam(':precio', $precio, PDO::PARAM_STR);
                $query->bindParam(':total', $total, PDO::PARAM_STR);
                
                try {
                    $resultado = $query->execute();
                    if ($resultado) {
                        $msg = "registrado";
                    } else {
                        $error = $query->errorInfo();
                        $msg = "Error al insertar: " . $error[2];
                    }
                } catch (Exception $e) {
                    $msg = "Excepción al insertar: " . $e->getMessage();
                }
            }
        }
    }

    echo json_encode($msg);
    die();

} elseif (isset($_POST['cambio'])) {
    if (empty($_POST['actual']) || empty($_POST['nueva'])) {
        $msg = 'Los campos están vacíos';
    } else {
        $id = $_SESSION['idUser'];
        $actual = md5($_POST['actual']);
        $nueva = md5($_POST['nueva']);

        // Consulta para verificar la contraseña actual del usuario
        $consulta = $conexion->prepare("SELECT * FROM usuario WHERE clave = :actual AND idusuario = :id");
        $consulta->bindParam(':actual', $actual, PDO::PARAM_STR);
        $consulta->bindParam(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
        $result = $consulta->rowCount();

        if ($result == 1) {
            // Actualizar la contraseña del usuario
            $query = $conexion->prepare("UPDATE usuario SET clave = :nueva WHERE idusuario = :id");
            $query->bindParam(':nueva', $nueva, PDO::PARAM_STR);
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();

            $msg = $query ? 'ok' : 'error';
        } else {
            $msg = 'dif';
        }
    }

    echo $msg;
    die();
}
