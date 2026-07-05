<?php
require_once 'db_config.php';
session_start();

$msg = "";

try {
    $checkCedula = $pdo->query("PRAGMA table_info(citas)");
    $columnas = $checkCedula->fetchAll(PDO::FETCH_COLUMN, 1);
    
    if (!in_array('cedula', $columnas)) {
        $pdo->exec("ALTER TABLE citas ADD COLUMN cedula TEXT;");
    }
    if (!in_array('motivo', $columnas)) {
        $pdo->exec("ALTER TABLE citas ADD COLUMN motivo TEXT;");
    }
} catch (Exception $e) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS citas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        paciente_nombre TEXT NOT NULL,
        cedula TEXT,
        especialidad TEXT NOT NULL,
        fecha TEXT NOT NULL,
        hora TEXT NOT NULL,
        motivo TEXT
    );");
}

function obtenerDiasHabilesDisponibles($cantidadDias = 30) {
    $dias = [];
    $current = new DateTime('tomorrow'); 
    
    while (count($dias) < $cantidadDias) {
        $diaSemana = $current->format('N'); 
        if ($diaSemana < 6) { 
            $dias[] = [
                'val'  => $current->format('Y-m-d'),
                'text' => $current->format('d/m/Y') . ' (' . obtenerNombreDia($diaSemana) . ')'
            ];
        }
        $current->modify('+1 day');
    }
    return $dias;
}

function obtenerNombreDia($num) {
    $nombres = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'];
    return $nombres[$num] ?? '';
}

$fechas_disponibles = obtenerDiasHabilesDisponibles(30);

if (isset($_GET['get_horas']) && isset($_GET['fecha']) && isset($_GET['especialidad'])) {
    header('Content-Type: application/json');
    $fecha_seleccionada = $_GET['fecha'];
    $especialidad_seleccionada = $_GET['especialidad'];
    
    $horas_posibles = [];
    $start = new DateTime('09:00');
    $end = new DateTime('18:30'); 
    $interval = new DateInterval('PT30M');
    $period = new DatePeriod($start, $interval, $end);
    
    foreach ($period as $dt) {
        $horas_posibles[] = $dt->format('H:i');
    }
    
    $stmt = $pdo->prepare("SELECT hora FROM citas WHERE fecha = ? AND especialidad = ?");
    $stmt->execute([$fecha_seleccionada, $especialidad_seleccionada]);
    $horas_ocupadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $horas_ocupadas = array_map(function($h) {
        return substr($h, 0, 5);
    }, $horas_ocupadas);

    $horas_disponibles = array_diff($horas_posibles, $horas_ocupadas);
    
    $resultado = [];
    foreach ($horas_disponibles as $hora) {
        $time_obj = new DateTime($hora);
        $resultado[] = [
            '24h'   => $hora,
            '12h'   => $time_obj->format('g:i A')
        ];
    }
    
    echo json_encode(array_values($resultado));
    exit;
}

if (isset($_POST['login'])) {
    $user_ingresado = $_POST['user'];
    $pass_ingresada = $_POST['pass'];
    
    if ($user_ingresado === 'GRUPO_H' && $pass_ingresada === 'GRUPO H') {
        $_SESSION['usuario'] = 'GRUPO_H';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND password = ?");
        $stmt->execute([$user_ingresado, $pass_ingresada]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['usuario'] = $user['usuario'];
        } else {
            $msg = "<p style='color:red; text-align:center;'>Acceso denegado. Verifique sus credenciales.</p>";
        }
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (isset($_GET['eliminar']) && isset($_SESSION['usuario'])) {
    $id_cita = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM citas WHERE id = ?");
    $stmt->execute([$id_cita]);
    $msg = "<p style='color:green; padding: 10px; background:#e6f4ea; border-radius:4px; margin-bottom:15px;'>Cita eliminada correctamente.</p>";
}

if (isset($_POST['agendar'])) {
    $fecha_ingresada = $_POST['fecha'];
    $hora_ingresada = $_POST['hora'];
    $especialidad_ingresada = $_POST['especialidad'];
    $cedula_ingresada = trim($_POST['cedula']);
    $motivo_ingresado = $_POST['motivo'];

    $dia_semana_servidor = date('N', strtotime($fecha_ingresada));

    $paciente_ingresado = trim($_POST['paciente']);

    if (!ctype_digit($cedula_ingresada) || strlen($cedula_ingresada) > 10 || strlen($cedula_ingresada) === 0) {
        $msg = "<p style='color:red; padding: 10px; background:#fce8e6; border-radius:4px; margin-bottom:15px;'>Error: La cédula debe contener solo números y máximo 10 dígitos.</p>";
    } elseif (!preg_match('/^[a-zA-ZÁÉÍÓÚÑáéíóúñ\s]+$/', $paciente_ingresado) || strlen($paciente_ingresado) === 0) {
        $msg = "<p style='color:red; padding: 10px; background:#fce8e6; border-radius:4px; margin-bottom:15px;'>Error: El nombre del paciente solo debe contener letras y espacios.</p>";
    } elseif ($dia_semana_servidor >= 6) {
        $msg = "<p style='color:red; padding: 10px; background:#fce8e6; border-radius:4px; margin-bottom:15px;'>Error: No se atienden sábados ni domingos.</p>";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE fecha = ? AND hora = ? AND especialidad = ?");
        $stmt->execute([$fecha_ingresada, $hora_ingresada, $especialidad_ingresada]);
        
        if ($stmt->fetchColumn() > 0) {
            $msg = "<p style='color:red; padding: 10px; background:#fce8e6; border-radius:4px; margin-bottom:15px;'>Error: Esta hora ya fue reservada para esta especialidad.</p>";
        } else {
            $sql = "INSERT INTO citas (paciente_nombre, cedula, especialidad, fecha, hora, motivo) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$paciente_ingresado, $cedula_ingresada, $especialidad_ingresada, $fecha_ingresada, $hora_ingresada, $motivo_ingresado]);
            $msg = "<p style='color:green; padding: 10px; background:#e6f4ea; border-radius:4px; margin-bottom:15px;'>Cita registrada correctamente.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Médico</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f3f6fd; color: #333; display: flex; flex-direction: column; min-height: 100vh; }
        
        .navbar { background-color: #003366; color: white; display: flex; justify-content: space-between; align-items: center; padding: 16px 32px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); width: 100%; }
        .navbar-brand { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
        .navbar-user { display: flex; align-items: center; gap: 15px; font-size: 14px; }
        .user-badge { background-color: #0056b3; padding: 6px 12px; border-radius: 20px; display: flex; align-items: center; gap: 6px; font-weight: bold; }
        .logout-btn { color: #ff6666; text-decoration: none; border: 1px solid #ff6666; padding: 6px 12px; border-radius: 6px; font-weight: bold; font-size: 13px; transition: 0.2s; }
        .logout-btn:hover { background-color: #ff6666; color: white; }

        .main-layout { display: flex; gap: 24px; padding: 24px 32px; flex: 1; width: 100%; max-width: 100%; margin: 0; }
        
        .left-column { flex: 1; display: flex; flex-direction: column; gap: 24px; min-width: 450px; }
        
        .right-column { flex: 1.8; display: flex; flex-direction: column; }

        .card { background: white; border-radius: 8px; border: 1px solid #e0e6ed; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card-title { font-size: 16px; font-weight: bold; color: #003366; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .instructions-card { background-color: #fafbfc; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #444; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #ccd4dc; border-radius: 6px; font-size: 14px; color: #333; background-color: white; outline: none; }
        .form-control:focus { border-color: #0056b3; box-shadow: 0 0 0 3px rgba(0,86,179,0.1); }
        .form-control:disabled { background-color: #f1f3f5; color: #888; cursor: not-allowed; }
        textarea.form-control { resize: vertical; min-height: 80px; }
        
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-row .form-group { flex: 1; margin-bottom: 0; }

        .btn-submit { width: 100%; background-color: #28a745; color: white; border: none; padding: 12px; font-size: 14px; font-weight: bold; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 20px; transition: 0.2s; }
        .btn-submit:hover { background-color: #218838; }

        .instructions-list { list-style: none; display: flex; flex-direction: column; gap: 15px; font-size: 13px; color: #555; }
        .instructions-list li { display: flex; align-items: flex-start; gap: 10px; line-height: 1.4; }

        .report-card { height: 100%; }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th { background-color: #e8f0fe; color: #003366; font-weight: bold; padding: 12px; border-bottom: 2px solid #ccd4dc; }
        td { padding: 12px; border-bottom: 1px solid #e0e6ed; color: #444; vertical-align: top; }
        tr:hover { background-color: #f8faff; }
        
        .btn-delete { color: #d9534f; text-decoration: none; font-weight: bold; font-size: 13px; display: flex; align-items: center; gap: 4px; }
        .btn-delete:hover { color: #c9302c; text-decoration: underline; }

        .footer { background-color: #002244; color: #cfd8dc; padding: 24px 32px; text-align: left; font-size: 13px; border-top: 4px solid #0056b3; margin-top: auto; width: 100%; }
        .footer-content { width: 100%; max-width: 100%; display: flex; flex-wrap: wrap; gap: 40px; justify-content: space-between; }
        .footer-section { flex: 1; min-width: 250px; }
        .footer-section h4 { color: #ffffff; font-size: 14px; margin-bottom: 10px; border-bottom: 1px solid #0056b3; padding-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .footer-section ul { list-style: none; }
        .footer-section ul li { margin-bottom: 5px; color: #b0bec5; }
        .footer-section p { color: #b0bec5; line-height: 1.5; }

        .login-wrapper { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f3f6fd; }
        .login-card { width: 100%; max-width: 400px; background: white; padding: 30px; border-radius: 8px; border: 1px solid #e0e6ed; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['usuario'])): ?>
    <div class="login-wrapper">
        <div class="login-card">
            <h2 style="text-align: center; color: #003366; margin-bottom: 20px;">Acceso al Sistema Médico</h2>
            <?= $msg ?>
            <form method="POST">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="user" class="form-control" placeholder="GRUPO_H" required>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="pass" class="form-control" placeholder="GRUPO H" required>
                </div>
                <button type="submit" name="login" class="btn-submit" style="background-color: #003366;">Ingresar</button>
            </form>
        </div>
    </div>

<?php else: ?>
    <header class="navbar">
        <div class="navbar-brand">
            Sistema Médico
        </div>
        <div class="navbar-user">
            <span>Bienvenido:</span>
            <div class="user-badge">👤 <?= htmlspecialchars($_SESSION['usuario']) ?></div>
            <a href="?logout=1" class="logout-btn">➔ Cerrar Sesión</a>
        </div>
    </header>

    <div class="main-layout">
        
        <div class="left-column">
            
            <div class="card">
                <div class="card-title">Agendar Cita Médica</div>
                
                <?= $msg ?>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group" style="flex: 1.5;">
                            <label>Nombre completo del paciente</label>
                            <input type="text" name="paciente" id="paciente" class="form-control" placeholder="Ingrese el nombre" 
                                   pattern="[a-zA-ZÁÉÍÓÚÑáéíóúñ\s]+" 
                                   title="Debe ingresar solo letras, sin números ni símbolos" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Número de Cédula</label>
                            <input type="text" name="cedula" id="cedula" class="form-control" placeholder="Ej: 0987654321" 
                                   maxlength="10" inputmode="numeric" pattern="[0-9]{1,10}" 
                                   title="Debe ingresar solo números, máximo 10 dígitos" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de Consulta / Especialidad</label>
                        <select id="especialidad" name="especialidad" class="form-control" required>
                            <option value="" disabled selected>Seleccione una opción...</option>
                            <option value="GINECOLOGIA">GINECOLOGÍA</option>
                            <option value="MEDICINA FAMILIAR">MEDICINA FAMILIAR</option>
                            <option value="MEDICINA GENERAL">MEDICINA GENERAL</option>
                            <option value="OBSTETRICIA">OBSTETRICIA</option>
                            <option value="ODONTOLOGIA">ODONTOLOGÍA</option>
                            <option value="PSICOLOGIA">PSICOLOGÍA</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Fecha disponible (Lun a Vie)</label>
                            <select id="fecha" name="fecha" class="form-control" required disabled>
                                <option value="" disabled selected>Seleccione primero una especialidad...</option>
                                <?php foreach ($fechas_disponibles as $fd): ?>
                                    <option value="<?= $fd['val'] ?>"><?= $fd['text'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Hora disponible</label>
                            <select id="hora" name="hora" class="form-control" required disabled>
                                <option value="">Seleccione fecha y especialidad...</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label>Motivo de la consulta</label>
                        <textarea name="motivo" class="form-control" placeholder="Escriba brevemente el motivo de su visita médica..." required></textarea>
                    </div>

                    <button type="submit" name="agendar" class="btn-submit">
                        Guardar
                    </button>
                </form>
            </div>

            <div class="card instructions-card">
                <div class="card-title" style="color: #0056b3;">Instrucciones</div>
                <ul class="instructions-list">
                    <li>👤 <span>Seleccione una especialidad primero</span></li>
                    <li>📅 <span>Luego elija una fecha disponible (Lunes a Viernes)</span></li>
                    <li>🕒 <span>Finalmente seleccione una hora disponible</span></li>
                    <li>✓ <span>Complete los datos, el motivo y guarde la cita</span></li>
                </ul>
            </div>
            
        </div>

        <div class="right-column">
            <div class="card report-card">
                <div class="card-title">Reporte de Citas (Persistencia Local)</div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 100px;">Cédula</th>
                                <th>Paciente</th>
                                <th>Especialidad</th>
                                <th>Fecha y Hora</th>
                                <th>Motivo</th>
                                <th style="width: 90px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM citas ORDER BY id DESC");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['cedula'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['paciente_nombre']) ?></td>
                                    <td><?= $row['especialidad'] ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($row['fecha'])) ?><br>
                                        <small style="color:#555; font-weight:bold;"><?= date('g:i A', strtotime($row['hora'])) ?></small>
                                    </td>
                                    <td><small><?= htmlspecialchars($row['motivo'] ?? 'No especificado') ?></small></td>
                                    <td>
                                        <a href="?eliminar=<?= $row['id'] ?>" 
                                           class="btn-delete"
                                           onclick="return confirm('¿Está seguro de que desea eliminar esta cita?');">
                                           Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Autores - Grupo H</h4>
                <ul>
                    <li>Mena Vélez Mateo Nikolas</li>
                    <li>Pontón Peña Priscila Indira</li>
                    <li>Preciado Nazareno Shirley Yasmin</li>
                    <li>Valarezo Ruíz Germania Cecibel</li>
                    <li>Zambrano Arrobo Steven Joel</li>
                    <li>Zapata Delgado Santiago Alexander</li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Asignatura</h4>
                <p>Interacción Humano-Computador</p>
            </div>
            <div class="footer-section">
                <h4>Docente</h4>
                <p>MSc. Alex Armando Avila Coello</p>
            </div>
        </div>
    </footer>

<?php endif; ?>

<script>
const selectEspecialidad = document.getElementById('especialidad');
const selectFecha = document.getElementById('fecha');
const selectHora = document.getElementById('hora');
const inputCedula = document.getElementById('cedula');
const inputPaciente = document.getElementById('paciente');

if (inputCedula) {
    inputCedula.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });
}

if (inputPaciente) {
    inputPaciente.addEventListener('input', function() {
        this.value = this.value.replace(/[^a-zA-ZÁÉÍÓÚÑáéíóúñ\s]/g, '');
    });
}

selectEspecialidad.addEventListener('change', function() {
    selectFecha.disabled = false;
    selectFecha.selectedIndex = 0; 
    selectHora.innerHTML = '<option value="">Seleccione fecha y especialidad...</option>';
    selectHora.disabled = true;
});

selectFecha.addEventListener('change', function() {
    const fecha = selectFecha.value;
    const especialidad = selectEspecialidad.value;
    
    if(!fecha || !especialidad) return;

    selectHora.innerHTML = '<option value="">Cargando horarios disponibles...</option>';
    selectHora.disabled = true;

    fetch(`index.php?get_horas=1&fecha=${fecha}&especialidad=${especialidad}`)
        .then(response => response.json())
        .then(horasLibres => {
            selectHora.innerHTML = '';
            
            if(horasLibres.length === 0) {
                selectHora.innerHTML = '<option value="">No hay horarios libres para esta especialidad</option>';
            } else {
                selectHora.innerHTML = '<option value="" disabled selected>Seleccione una hora...</option>';
                horasLibres.forEach(hora => {
                    const option = document.createElement('option');
                    option.value = hora['24h'];
                    option.textContent = hora['12h'];
                    selectHora.appendChild(option);
                });
                selectHora.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            selectHora.innerHTML = '<option value="">Error al cargar horarios</option>';
        });
});
</script>

</body>
</html>