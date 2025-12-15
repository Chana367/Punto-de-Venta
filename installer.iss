[Setup]
AppName=Punto de Venta
AppVersion=1.0
DefaultDirName={commonpf}\punto_de_venta
DefaultGroupName=Punto de Venta
OutputDir=.\Output
OutputBaseFilename=puntodeventainstalador
Compression=lzma2
SolidCompression=yes
Encryption=yes
Password=puntoVenta2
PrivilegesRequired=admin

[Files]
; Incluir archivos específicos del punto de venta solamente
Source: "*.php"; DestDir: "{app}"; Flags: ignoreversion
Source: "*.js"; DestDir: "{app}"; Flags: ignoreversion
Source: "*.json"; DestDir: "{app}"; Flags: ignoreversion
Source: "*.vbs"; DestDir: "{app}"; Flags: ignoreversion
Source: "*.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "*.ico"; DestDir: "{app}"; Flags: ignoreversion
Source: "*.sqbpro"; DestDir: "{app}"; Flags: ignoreversion
Source: "*.db"; DestDir: "{app}"; Flags: ignoreversion
Source: "assets\*"; DestDir: "{app}\assets"; Flags: recursesubdirs createallsubdirs ignoreversion
Source: "src\*"; DestDir: "{app}\src"; Flags: recursesubdirs createallsubdirs ignoreversion
Source: "php\*"; DestDir: "{app}\php"; Flags: recursesubdirs createallsubdirs ignoreversion
; Incluir Node.js portable si existe en tu proyecto
Source: "node\*"; DestDir: "{app}\node"; Flags: recursesubdirs createallsubdirs ignoreversion; Check: DirExists(ExpandConstant('{src}\node'))

[Icons]
Name: "{group}\Punto de Venta"; Filename: "{app}\ejecutar_app.vbs"; WorkingDir: "{app}"
Name: "{commondesktop}\Punto de Venta"; Filename: "{app}\ejecutar_app.vbs"; WorkingDir: "{app}"; IconFilename: "{app}\printer_4469875.ico"

[Registry]
Root: HKLM; Subkey: "Software\PuntoVenta"; ValueType: string; ValueName: "InstallDir"; ValueData: "{app}"; Flags: uninsdeletekey

[Run]
Filename: "{app}\ejecutar_app.vbs"; Description: "{cm:LaunchProgram,Punto de Venta}"; Flags: shellexec postinstall skipifsilent