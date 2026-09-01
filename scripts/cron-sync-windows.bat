@echo off
rem Pit o Cuixa - scheduled task entry point for dinahosting (Windows hosting).
rem The panel's "Ruta del script" must point to THIS file.
rem It simply runs the PowerShell sync that sits next to it, which reads the
rem token from the project .env and POSTs to /api/update-menu.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0cron-sync-windows.ps1"
