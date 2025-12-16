<?php
session_start();
require_once "../conexion.php";
$id = $_GET['id'];
$id_user_actual = $_SESSION['idUser'];

// Validaciones de seguridad
// 1. No permitir editar permisos del admin principal (ID=1)
// 2. No permitir que un usuario edite sus propios permisos
if ($id == 1) {
    header("Location: usuarios.php");
    exit;
}

if ($id == $id_user_actual) {
    header("Location: usuarios.php");
    exit;
}

// Consultar permisos del usuario
$sqlpermisos = $conexion->prepare("SELECT * FROM permisos");
$sqlpermisos->execute();
$usuarios = $conexion->prepare("SELECT * FROM usuario WHERE idusuario = :id");
$usuarios->bindParam(':id', $id, PDO::PARAM_INT);
$usuarios->execute();
$consulta = $conexion->prepare("SELECT * FROM detalle_permisos WHERE id_usuario = :id");
$consulta->bindParam(':id', $id, PDO::PARAM_INT);
$consulta->execute();
$resultUsuario = $usuarios->fetchAll(PDO::FETCH_ASSOC);

if (empty($resultUsuario)) {
    header("Location: usuarios.php");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_GET['id'];
    
    // Detectar permisos marcados desde el array permisos[] o desde las acciones individuales
    $permisos_array = $_POST['permisos'] ?? [];
    
    // Si no hay permisos[] pero hay acciones marcadas, construir el array
    if (empty($permisos_array)) {
        $permisos_detectados = [];
        foreach ($_POST as $key => $value) {
            if (preg_match('/^(crear|leer|actualizar|eliminar)_(\d+)$/', $key, $matches)) {
                $permiso_id = $matches[2];
                if (!in_array($permiso_id, $permisos_detectados)) {
                    $permisos_detectados[] = $permiso_id;
                }
            }
        }
        $permisos_array = $permisos_detectados;
    }
    
    // Eliminar permisos anteriores
    $delete = $conexion->prepare("DELETE FROM detalle_permisos WHERE id_usuario = :id_user");
    $delete->execute([':id_user' => $id_user]);

    if (!empty($permisos_array)) {
        $errores = 0;
        foreach ($permisos_array as $permiso_id) {
            $puede_crear = isset($_POST['crear_' . $permiso_id]) ? 1 : 0;
            $puede_leer = isset($_POST['leer_' . $permiso_id]) ? 1 : 0;
            $puede_actualizar = isset($_POST['actualizar_' . $permiso_id]) ? 1 : 0;
            $puede_eliminar = isset($_POST['eliminar_' . $permiso_id]) ? 1 : 0;
            
            $sql = $conexion->prepare("INSERT INTO detalle_permisos(id_usuario, id_permiso, puede_crear, puede_leer, puede_actualizar, puede_eliminar) VALUES (:id_user, :permiso, :crear, :leer, :actualizar, :eliminar)");
            $result = $sql->execute([
                ':id_user' => $id_user,
                ':permiso' => $permiso_id,
                ':crear' => $puede_crear,
                ':leer' => $puede_leer,
                ':actualizar' => $puede_actualizar,
                ':eliminar' => $puede_eliminar
            ]);
            
            if (!$result) {
                $errores++;
                file_put_contents('log_post_debug.txt', "Error en permiso $permiso_id\n", FILE_APPEND);
            }
        }
        
        if ($errores === 0) {
            $alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>¡Éxito!</strong> Los permisos han sido actualizados correctamente.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>';
        } else {
            $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> Algunos permisos no se guardaron correctamente.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>';
        }
    }
    
    // Recargar permisos después de guardar
    $consulta = $conexion->prepare("SELECT * FROM detalle_permisos WHERE id_usuario = :id");
    $consulta->bindParam(':id', $id, PDO::PARAM_INT);
    $consulta->execute();
}

// Cargar permisos actuales con sus acciones
$datos = array();
foreach ($consulta as $asignado) {
    $datos[$asignado['id_permiso']] = [
        'activo' => true,
        'puede_crear' => $asignado['puede_crear'] ?? 0,
        'puede_leer' => $asignado['puede_leer'] ?? 0,
        'puede_actualizar' => $asignado['puede_actualizar'] ?? 0,
        'puede_eliminar' => $asignado['puede_eliminar'] ?? 0
    ];
}
include_once "includes/header.php";
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card shadow-lg">
            <div class="card-header card-header-primary">
                <h4 class="mb-0">Asignar Permisos a: <strong><?php echo $resultUsuario[0]['nombre']; ?></strong></h4>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <?php echo (isset($alert)) ? $alert : ''; ?>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Instrucciones:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Marcar el módulo</strong> para dar acceso general</li>
                            <li><strong>Crear:</strong> Permite agregar nuevos registros</li>
                            <li><strong>Leer:</strong> Permite ver/consultar registros (solo lectura)</li>
                            <li><strong>Actualizar:</strong> Permite editar registros existentes</li>
                            <li><strong>Eliminar:</strong> Permite borrar registros</li>
                        </ul>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width: 30%;">Módulo</th>
                                    <th class="text-center" style="width: 15%;">
                                        <i class="fas fa-plus-circle"></i> Crear
                                    </th>
                                    <th class="text-center" style="width: 15%;">
                                        <i class="fas fa-eye"></i> Leer
                                    </th>
                                    <th class="text-center" style="width: 15%;">
                                        <i class="fas fa-edit"></i> Actualizar
                                    </th>
                                    <th class="text-center" style="width: 15%;">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </th>
                                    <th class="text-center" style="width: 10%;">
                                        <i class="fas fa-check-double"></i> Todos
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Detectar si el usuario actual es administrador
                                $es_admin = isset($_SESSION['idUser']) && $_SESSION['idUser'] == 1;
                                
                                $sqlpermisos->execute(); // Re-ejecutar para iterar
                                while ($row = $sqlpermisos->fetch(PDO::FETCH_ASSOC)) { 
                                    $permiso_id = $row['id'];
                                    $tiene_permiso = isset($datos[$permiso_id]['activo']);
                                    $puede_crear = isset($datos[$permiso_id]) ? $datos[$permiso_id]['puede_crear'] : 0;
                                    $puede_leer = isset($datos[$permiso_id]) ? $datos[$permiso_id]['puede_leer'] : 0;
                                    $puede_actualizar = isset($datos[$permiso_id]) ? $datos[$permiso_id]['puede_actualizar'] : 0;
                                    $puede_eliminar = isset($datos[$permiso_id]) ? $datos[$permiso_id]['puede_eliminar'] : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" 
                                                   class="form-check-input permiso-principal" 
                                                   name="permisos[]" 
                                                   value="<?php echo $permiso_id; ?>" 
                                                   id="permiso_<?php echo $permiso_id; ?>"
                                                   <?php echo $tiene_permiso ? 'checked' : ''; ?>
                                                   <?php if (!$es_admin): ?>
                                                   onchange="toggleAcciones(<?php echo $permiso_id; ?>)"
                                                   <?php endif; ?>>
                                            <label class="form-check-label text-uppercase font-weight-bold" for="permiso_<?php echo $permiso_id; ?>">
                                                <?php echo $row['nombre']; ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" 
                                               class="accion-check" 
                                               name="crear_<?php echo $permiso_id; ?>" 
                                               id="crear_<?php echo $permiso_id; ?>"
                                               <?php echo ($tiene_permiso && $puede_crear) ? 'checked' : ''; ?>>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" 
                                               class="accion-check" 
                                               name="leer_<?php echo $permiso_id; ?>" 
                                               id="leer_<?php echo $permiso_id; ?>"
                                               <?php echo ($tiene_permiso && $puede_leer) ? 'checked' : ''; ?>>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" 
                                               class="accion-check" 
                                               name="actualizar_<?php echo $permiso_id; ?>" 
                                               id="actualizar_<?php echo $permiso_id; ?>"
                                               <?php echo ($tiene_permiso && $puede_actualizar) ? 'checked' : ''; ?>>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" 
                                               class="accion-check" 
                                               name="eliminar_<?php echo $permiso_id; ?>" 
                                               id="eliminar_<?php echo $permiso_id; ?>"
                                               <?php echo ($tiene_permiso && $puede_eliminar) ? 'checked' : ''; ?>>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary" 
                                                onclick="marcarTodos(<?php echo $permiso_id; ?>)"
                                                id="btnTodos_<?php echo $permiso_id; ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="fas fa-save"></i> Guardar Permisos
                        </button>
                        <a href="usuarios.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Cuando se marque/desmarque el checkbox principal del módulo
function toggleAcciones(permisoId) {
    const checkbox = document.getElementById('permiso_' + permisoId);
    const isChecked = checkbox.checked;
    
    // Habilitar/deshabilitar checkboxes de acciones
    ['crear', 'leer', 'actualizar', 'eliminar'].forEach(accion => {
        const accionCheck = document.getElementById(accion + '_' + permisoId);
        accionCheck.disabled = !isChecked;
        if (!isChecked) {
            accionCheck.checked = false;
        }
    });
    
    // Habilitar/deshabilitar botón "Todos"
    const btnTodos = document.getElementById('btnTodos_' + permisoId);
    btnTodos.disabled = !isChecked;
}

// Marcar todos los permisos de un módulo
function marcarTodos(permisoId) {
    const moduloCheck = document.getElementById('permiso_' + permisoId);
    moduloCheck.checked = true;
    
    ['crear', 'leer', 'actualizar', 'eliminar'].forEach(accion => {
        const accionCheck = document.getElementById(accion + '_' + permisoId);
        if (!accionCheck.disabled) {
            accionCheck.checked = true;
        }
    });
}

// Cuando se marca una acción individual, marcar automáticamente el módulo principal
document.addEventListener('DOMContentLoaded', function() {
    // Agregar listener a todos los checkboxes de acciones
    document.querySelectorAll('.accion-check').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Extraer el ID del permiso del name del checkbox (ej: "crear_6" -> "6")
            const match = this.name.match(/^(crear|leer|actualizar|eliminar)_(\d+)$/);
            if (match) {
                const permisoId = match[2];
                const moduloCheck = document.getElementById('permiso_' + permisoId);
                
                // Si se marca cualquier acción, asegurarse de que el módulo esté marcado
                if (this.checked && moduloCheck) {
                    moduloCheck.checked = true;
                }
                
                // Si se desmarca y ninguna otra acción está marcada, desmarcar el módulo
                if (!this.checked && moduloCheck) {
                    const algunaMarcada = ['crear', 'leer', 'actualizar', 'eliminar'].some(accion => {
                        const accionCheck = document.getElementById(accion + '_' + permisoId);
                        return accionCheck && accionCheck.checked;
                    });
                    
                    if (!algunaMarcada) {
                        moduloCheck.checked = false;
                    }
                }
            }
        });
    });
});
</script>
<?php include_once "includes/footer.php"; ?>
