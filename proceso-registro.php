<?php
require_once 'db.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'errors' => [],
    'redirect' => ''
];

try {
    // Verificar si es una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Determinar si es registro normal o de aliado
    $esAliado = isset($_POST['nombre_local']) && isset($_POST['descripcion']);
    
    // Validar campos según tipo de registro
    if ($esAliado) {
        // Validación para aliados
        $requiredFields = [
            'nombre_local', 'descripcion', 'nombre', 'email', 
            'telefono', 'password', 'confirmPassword', 'terms', 'rol', 'direccion'
        ];
    } else {
        // Validación para usuarios normales
        $requiredFields = [
            'nombre', 'email', 'telefono', 'ciudad', 
            'petNombre', 'petEdad', 'petPeso', 'petSexo',
            'password', 'confirmPassword', 'terms'
        ];
    }
    
    $errors = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $errors[$field] = 'Este campo es requerido';
        }
    }
    
    // Validaciones específicas
    if (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email no válido';
    }
    
    if (isset($_POST['password']) && strlen($_POST['password']) < 8) {
        $errors['password'] = 'Mínimo 8 caracteres';
    }
    
    if (isset($_POST['password']) && isset($_POST['confirmPassword']) && 
        $_POST['password'] !== $_POST['confirmPassword']) {
        $errors['confirmPassword'] = 'Las contraseñas no coinciden';
    }
    
    // Si hay errores, retornarlos
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Por favor corrige los errores';
        echo json_encode($response);
        exit;
    }
    
    // Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    if ($stmt->fetch()) {
        throw new Exception('El email ya está registrado');
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // 1. Insertar usuario
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Determinar el rol
        if ($esAliado) {
            $rol = $_POST['rol']; // veterinaria o tienda
        } else {
            $rol = $_POST['rol'] ?? 'usuario'; // usuario normal
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios 
            (nombre, email, telefono, ciudad, password, newsletter, rol, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $ciudad = $esAliado ? ($_POST['ciudad'] ?? 'Cali') : $_POST['ciudad'];
        
        $stmt->execute([
            $_POST['nombre'],
            $_POST['email'],
            $_POST['telefono'],
            $ciudad,
            $hashedPassword,
            isset($_POST['newsletter']) ? 1 : 0,
            $rol
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // 2. Para usuarios normales: insertar mascota
        if ($rol === 'usuario') {
            // Subir foto de la mascota si existe
            $petPhoto = null;
            if (isset($_FILES['petFoto']) && $_FILES['petFoto']['error'] == UPLOAD_ERR_OK) {
                $petPhoto = uploadPhoto($_FILES['petFoto']);
            }
            
            // Generar código QR
            $qrCode = 'RUGAL-' . strtoupper(substr(md5($userId . $_POST['petNombre'] . time()), 0, 10));
            
            // Calcular edad en años y meses desde el campo petEdad
            $edadAnios = 0;
            $edadMeses = 0;
            
            // Primero intentar campos separados si existen
            if (isset($_POST['petEdadAnios']) && is_numeric($_POST['petEdadAnios'])) {
                $edadAnios = intval($_POST['petEdadAnios']);
            }
            if (isset($_POST['petEdadMeses']) && is_numeric($_POST['petEdadMeses'])) {
                $edadMeses = intval($_POST['petEdadMeses']);
            }
            
            // Si no hay campos separados, intentar parsear petEdad
            if ($edadAnios == 0 && $edadMeses == 0 && isset($_POST['petEdad']) && !empty($_POST['petEdad'])) {
                $edadValor = floatval($_POST['petEdad']);
                $edadAnios = intval($edadValor);
                $edadMeses = intval(round(($edadValor - $edadAnios) * 12));
            }
            
            // Asegurar que meses esté entre 0-11
            if ($edadMeses >= 12) {
                $edadAnios += intval($edadMeses / 12);
                $edadMeses = $edadMeses % 12;
            }

            
            // Insertar mascota
            $stmt = $pdo->prepare("
                INSERT INTO mascotas 
                (user_id, nombre, tipo, raza, edad, edad_anios, edad_meses, peso, peso_promedio, color, sexo, 
                 nivel_actividad, foto_perfil, codigo_qr, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $_POST['petNombre'],
                $_POST['petTipo'] ?? 'perro',
                $_POST['petRaza'] ?? null,
                $_POST['petEdad'],
                $edadAnios,
                $edadMeses,
                $_POST['petPeso'],
                $_POST['petPeso'],
                $_POST['petColor'] ?? null,
                $_POST['petSexo'],
                'medio',
                $petPhoto,
                $qrCode
            ]);

            
            $petId = $pdo->lastInsertId();
            
            // Insertar peso en historial
            $stmt = $pdo->prepare("
                INSERT INTO peso_historial (mascota_id, peso, fecha) 
                VALUES (?, ?, CURDATE())
            ");
            $stmt->execute([$petId, $_POST['petPeso']]);
        } else {
            $petId = null;
        }
        
        // 3. Para aliados: crear registro en tabla aliados
        if (in_array($rol, ['veterinaria', 'tienda'])) {
            // Subir fotos de verificacion
            $fotosGuardadas = [];
            $uploadsDir = __DIR__ . '/uploads/aliados_verificacion/';
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
            
            if (!empty($_FILES['fotos_local']['name'][0])) {
                $maxFotos = 3;
                $count = 0;
                foreach ($_FILES['fotos_local']['tmp_name'] as $idx => $tmpName) {
                    if ($count >= $maxFotos) break;
                    if ($_FILES['fotos_local']['error'][$idx] !== UPLOAD_ERR_OK) continue;
                    $ext = strtolower(pathinfo($_FILES['fotos_local']['name'][$idx], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;
                    $newName = 'aliado_' . $userId . '_' . time() . '_' . $count . '.' . $ext;
                    if (move_uploaded_file($tmpName, $uploadsDir . $newName)) {
                        $fotosGuardadas[] = 'uploads/aliados_verificacion/' . $newName;
                        $count++;
                    }
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO aliados 
                (usuario_id, tipo, nombre_local, descripcion, direccion,
                 fotos_verificacion, precio_consulta, servicios, 
                 tipo_alimento, razas_recomendadas, activo, pendiente_verificacion, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $rol,
                $_POST['nombre_local'],
                $_POST['descripcion'],
                $_POST['direccion'] ?? null,
                json_encode($fotosGuardadas),
                $_POST['precio_consulta'] ?? null,
                $_POST['servicios'] ?? null,
                $_POST['tipo_alimento'] ?? null,
                $_POST['razas_recomendadas'] ?? null
            ]);
        }
        
        // Confirmar transacción
        $pdo->commit();
        
        // Iniciar sesión
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $_POST['email'];
        $_SESSION['user_name'] = $_POST['nombre'];
        $_SESSION['user_rol'] = $rol;
        $_SESSION['pet_id'] = $petId;
        
        // Determinar redirección según rol
        if ($esAliado) {
            // Aliados quedan pendientes de verificacion - no redirigir
            $response['success'] = true;
            $response['message'] = '¡Solicitud enviada! El admin verificará tu cuenta.';
            $response['redirect'] = '';
            $response['pending'] = true;
        } else {
            switch ($rol) {
                case 'admin':     $redirect = 'admin-dashboard.php'; break;
                case 'veterinaria': $redirect = 'vet-dashboard.php'; break;
                case 'tienda':    $redirect = 'tienda-dashboard.php'; break;
                default:          $redirect = 'dashboard.php'; break;
            }
            $response['success'] = true;
            $response['message'] = '¡Registro exitoso!';
            $response['redirect'] = $redirect;
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
