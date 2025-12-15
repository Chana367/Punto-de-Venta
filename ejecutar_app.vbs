Dim WshShell, InstallDir, RegKey
Set WshShell = CreateObject("WScript.Shell")

' Leer la ruta de instalación desde el registro
RegKey = "HKCU\Software\PuntoVenta\InstallDir"
InstallDir = WshShell.RegRead(RegKey)

' Construir la ruta completa al archivo BAT
WshShell.Run chr(34) & InstallDir & "\iniciar_app.bat" & Chr(34), 0

Set WshShell = Nothing
