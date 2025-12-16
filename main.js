const { app, BrowserWindow } = require('electron');
const http = require('http');
const path = require('path');
const fs = require('fs');
const { spawn, exec } = require('child_process');

let mainWindow;

// Función para matar procesos PHP del proyecto
function killPHPProcesses() {
  const projectPath = __dirname;
  
  if (process.platform === 'win32') {
    // Buscar y matar procesos PHP relacionados con este proyecto
    exec(`wmic process where "name='php.exe' and CommandLine like '%${projectPath.replace(/\\/g, '\\\\')}%'" call terminate`, (error) => {
      if (error) {
        console.log('No hay procesos PHP que cerrar o ya fueron cerrados');
      } else {
        console.log('Procesos PHP cerrados correctamente');
      }
    });
  }
}

// Función para eliminar archivo de bloqueo
function removeLockFile() {
  // Usar carpeta temporal del usuario para evitar problemas de permisos
  const tempDir = process.env.TEMP || process.env.TMP || '/tmp';
  const lockFile = path.join(tempDir, 'punto_venta.lock');
  
  if (fs.existsSync(lockFile)) {
    try {
      fs.unlinkSync(lockFile);
      console.log('Archivo de bloqueo eliminado');
    } catch (error) {
      console.error('Error al eliminar archivo de bloqueo:', error);
    }
  }
}

// Verificar e inicializar la base de datos si no existe
function initializeDatabase() {
  // Usar APPDATA para evitar problemas de permisos en Program Files
  const appDataDir = path.join(process.env.APPDATA || process.env.HOME, 'PuntoVenta');
  const dbPath = path.join(appDataDir, 'sistema.db');
  
  // Crear directorio si no existe
  if (!fs.existsSync(appDataDir)) {
    fs.mkdirSync(appDataDir, { recursive: true });
  }
  
  // Si no existe la base de datos, inicializarla
  if (!fs.existsSync(dbPath)) {
    console.log('Base de datos no encontrada. Inicializando...');
    
    const phpPath = path.join(__dirname, 'php', 'php.exe');
    const initScript = path.join(__dirname, 'inicializar_bd.php');
    
    return new Promise((resolve, reject) => {
      const phpProcess = spawn(phpPath, [initScript, dbPath], {
        cwd: __dirname
      });
      
      phpProcess.stdout.on('data', (data) => {
        console.log(`Inicialización: ${data}`);
      });
      
      phpProcess.stderr.on('data', (data) => {
        console.error(`Error: ${data}`);
      });
      
      phpProcess.on('close', (code) => {
        if (code === 0) {
          console.log('Base de datos inicializada correctamente');
          resolve();
        } else {
          console.error('Error al inicializar la base de datos');
          reject(new Error('Error en inicialización'));
        }
      });
    });
  }
  
  return Promise.resolve();
}

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1500,
    height: 800,
    webPreferences: {
      nodeIntegration: true,
    },
  });

  const loadingHTML = `
    <html>
      <head>
        <style>
          body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f0f0f0;
          }
          .message-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
          }
          .message-container h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
          }
          .message-container p {
            margin-top: 10px;
            font-size: 18px;
            color: #666;
          }
          .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
          }
          @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
          }
        </style>
      </head>
      <body>
        <div class="message-container">
          <h2>🏪 Punto de Venta</h2>
          <div class="spinner"></div>
          <p>Cargando sistema...</p>
        </div>
      </body>
    </html>
  `;

  mainWindow.loadURL(`data:text/html,${encodeURIComponent(loadingHTML)}`);

  // Intentar cargar la URL del servidor repetidamente
  checkServerAndLoadURL('http://127.0.0.1:8000', mainWindow);
}

function checkServerAndLoadURL(url, window) {
  const retryInterval = 3000; // 3 segundos

  function tryLoadURL() {
    http.get(url, (res) => {
      console.log(`Server response status: ${res.statusCode}`);
      if (res.statusCode === 200) {
        window.loadURL(url);
      } else {
        setTimeout(tryLoadURL, retryInterval);
      }
    }).on('error', (err) => {
      console.error(`Error connecting to server: ${err.message}`);
      setTimeout(tryLoadURL, retryInterval);
    });
  }

  tryLoadURL();
}

app.on('ready', async () => {
  try {
    await initializeDatabase();
    createWindow();
  } catch (error) {
    console.error('Error durante la inicialización:', error);
    app.quit();
  }
});

app.on('window-all-closed', () => {
  console.log('Cerrando aplicación...');
  killPHPProcesses();
  removeLockFile();
  
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('before-quit', () => {
  console.log('Limpiando recursos antes de cerrar...');
  killPHPProcesses();
  removeLockFile();
});

app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0) {
    createWindow();
  }
});
