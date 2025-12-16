Dim WshShell, InstallDir, RegKey, fso, objWMIService, colProcesses, objProcess
Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
Set objWMIService = GetObject("winmgmts:\\.\root\cimv2")

' Leer la ruta de instalación desde el registro
On Error Resume Next
RegKey = "HKCU\Software\PuntoVenta\InstallDir"
InstallDir = WshShell.RegRead(RegKey)

' Si no está en HKCU, intentar HKLM
If InstallDir = "" Then
    RegKey = "HKLM\Software\PuntoVenta\InstallDir"
    InstallDir = WshShell.RegRead(RegKey)
End If
On Error GoTo 0

If InstallDir = "" Then
    MsgBox "No se pudo encontrar la instalación del Punto de Venta.", vbCritical, "Error"
    WScript.Quit
End If

' Verificar si ya hay una instancia ejecutándose (buscar electron.exe con el directorio del proyecto)
Set colProcesses = objWMIService.ExecQuery("Select * from Win32_Process Where Name = 'electron.exe'")
For Each objProcess in colProcesses
    If InStr(objProcess.CommandLine, InstallDir) > 0 Then
        MsgBox "El Punto de Venta ya está en ejecución.", vbInformation, "Información"
        WScript.Quit
    End If
Next

' Crear archivo de bloqueo en la carpeta temporal del usuario (evita problemas de permisos)
Dim lockFile, tempFolder
tempFolder = WshShell.ExpandEnvironmentStrings("%TEMP%")
lockFile = tempFolder & "\punto_venta.lock"

If fso.FileExists(lockFile) Then
    ' Si existe el archivo de bloqueo, verificar si el proceso realmente está corriendo
    Dim lockIsValid
    lockIsValid = False
    Set colProcesses = objWMIService.ExecQuery("Select * from Win32_Process Where Name = 'electron.exe'")
    For Each objProcess in colProcesses
        If InStr(objProcess.CommandLine, InstallDir) > 0 Then
            lockIsValid = True
            Exit For
        End If
    Next
    
    If lockIsValid Then
        MsgBox "El Punto de Venta ya está en ejecución.", vbInformation, "Información"
        WScript.Quit
    Else
        ' El proceso no está corriendo pero el archivo de bloqueo existe, eliminarlo
        On Error Resume Next
        fso.DeleteFile lockFile, True
        On Error GoTo 0
    End If
End If

' Crear archivo de bloqueo
On Error Resume Next
Dim lockFileObj
Set lockFileObj = fso.CreateTextFile(lockFile, True)
If Err.Number = 0 Then
    lockFileObj.WriteLine Now
    lockFileObj.Close
End If
On Error GoTo 0

' Matar procesos PHP antiguos del proyecto antes de iniciar
Set colProcesses = objWMIService.ExecQuery("Select * from Win32_Process Where Name = 'php.exe'")
For Each objProcess in colProcesses
    If InStr(objProcess.CommandLine, InstallDir) > 0 Then
        objProcess.Terminate()
    End If
Next

' Esperar un momento para que los procesos terminen
WScript.Sleep 1000

' Construir la ruta completa al archivo BAT
Dim batPath
batPath = InstallDir & "\iniciar_app.bat"

' Ejecutar el BAT minimizado (ventana oculta pero procesos activos)
WshShell.Run chr(34) & batPath & Chr(34), 7, False

Set WshShell = Nothing
Set fso = Nothing
Set objWMIService = Nothing
