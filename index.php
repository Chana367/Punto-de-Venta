<?php
session_start();

// Obtener configuración del sistema
require_once "conexion.php";
$config_query = $conexion->query("SELECT * FROM configuracion LIMIT 1");
$config_data = $config_query->fetch(PDO::FETCH_ASSOC);
$logo_sistema = $config_data['logo'] ?? 'logo.png';
$background_sistema = $config_data['background'] ?? 'sidebar-1.jpg';
$nombre_empresa = $config_data['nombre'] ?? 'Punto de Venta';
$telefono_empresa = $config_data['telefono'] ?? '';
$email_empresa = $config_data['email'] ?? '';
$direccion_empresa = $config_data['direccion'] ?? '';

if (!empty($_SESSION['active'])) {
    header('Location: src/');
    exit();
} else {
    if (!empty($_POST)) {
        $alert = '';
        if (empty($_POST['usuario']) || empty($_POST['clave'])) {
            $alert = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                        Ingrese usuario y contraseña
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>';
        } else {

            // Obtener los datos enviados por POST
            $user = htmlspecialchars($_POST['usuario'], ENT_QUOTES, 'UTF-8');
            $clave = md5(htmlspecialchars($_POST['clave'], ENT_QUOTES, 'UTF-8')); // Encriptar la contraseña con MD5

            try {
                // Preparar y ejecutar la consulta
                $query = $conexion->prepare("SELECT * FROM usuario WHERE usuario = :usuario AND clave = :clave");
                $query->bindParam(':usuario', $user, PDO::PARAM_STR);
                $query->bindParam(':clave', $clave, PDO::PARAM_STR);
                $query->execute();

                $resultado = $query->fetch(PDO::FETCH_ASSOC);

                if ($resultado) {
                    // Crear sesión con los datos del usuario
                    $_SESSION['active'] = true;
                    $_SESSION['idUser'] = $resultado['idusuario'];
                    $_SESSION['nombre'] = $resultado['nombre'];
                    $_SESSION['user'] = $resultado['usuario'];
                    header('Location: src/');
                    exit();
                } else {
                    $alert = '<div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                                <strong>Error:</strong> Usuario o contraseña incorrectos.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
                    session_destroy();
                }
            } catch (PDOException $e) {
                $alert = '<div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                            <strong>Error:</strong> Problema al consultar la base de datos.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nombre_empresa); ?> - Iniciar Sesión</title>
    <link rel="icon" href="src/get_image.php?file=<?php echo urlencode($logo_sistema); ?>" type="image/png">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/js/all.min.js">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
        }

        .brand-section {
            flex: 1;
            background-color: rgba(0, 123, 255, 0.8);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .brand-section h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
            text-align: center;
        }

        .brand-section img {
            max-width: 150px;
            max-height: 150px;
            margin-bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 10px;
        }
        
        .brand-section .info {
            text-align: center;
            margin-top: 20px;
        }
        
        .brand-section .info p {
            margin: 5px 0;
            font-size: 0.95em;
        }

        .login-form-section {
            flex: 1;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
        }

        .login-form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('src/get_image.php?file=<?php echo urlencode($background_sistema); ?>') no-repeat center center;
            background-size: cover;
            z-index: -1;
            opacity: 0.6;
        }

        .login-form {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-form h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #007bff;
            font-weight: bold;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(-10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-form .form-control {
            border-radius: 20px;
            margin-bottom: 15px;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            transition: all 0.3s ease-in-out;
        }

        .login-form .form-control:focus {
            border-color: #007bff;
            box-shadow: 0px 0px 8px rgba(0, 123, 255, 0.6);
            outline: none;
        }

        .login-form button {
            width: 100%;
            background-color: #007bff;
            color: white;
            padding: 10px;
            font-size: 18px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-form button:hover {
            background-color: #0056b3;
        }

        .login-form .alert {
            margin-top: flex;
        }
    </style>
</head>
<body>
    <div class="brand-section">
        <img src="src/get_image.php?file=<?php echo urlencode($logo_sistema); ?>" alt="Logo <?php echo htmlspecialchars($nombre_empresa); ?>" onerror="this.style.display='none'">
        <h1><?php echo htmlspecialchars($nombre_empresa); ?></h1>
        <div class="info">
            <?php if (!empty($telefono_empresa)): ?>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($telefono_empresa); ?></p>
            <?php endif; ?>
            <?php if (!empty($email_empresa)): ?>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email_empresa); ?></p>
            <?php endif; ?>
            <?php if (!empty($direccion_empresa)): ?>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($direccion_empresa); ?></p>
            <?php endif; ?>
        </div>
    </div>
    </div>
    <div class="login-form-section">
        <form action="" method="POST" class="login-form">
            <?php echo (isset($alert)) ? $alert : ''; ?>
            <h2>Iniciar Sesión</h2>
            <input type="text" name="usuario" id="usuario" class="form-control" placeholder="Usuario" required>
            <input type="password" name="clave" id="clave" class="form-control" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>   
        </form>
    </div>
    
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
