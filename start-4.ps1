# ==============================================================
# Start-4.ps1 - GOD MODE Dev Environment Manager (Simple WPF GUI)
# Note: This is a lightweight GUI launcher using Windows Presentation Foundation
# It requires running in an elevated PowerShell session to start services/ports.
# ==============================================================

Add-Type -AssemblyName PresentationFramework

[xml]$xaml = @"
<Window xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation" Title="PreIPOsip Dev Launcher" Height="380" Width="720">
  <Grid Margin="10">
    <Grid.RowDefinitions>
      <RowDefinition Height="Auto" />
      <RowDefinition Height="*" />
      <RowDefinition Height="Auto" />
    </Grid.RowDefinitions>
    <StackPanel Orientation="Horizontal" HorizontalAlignment="Left" Margin="0,0,0,10">
      <Button Name="StartAll" Width="100" Margin="4">Start</Button>
      <Button Name="StopAll" Width="100" Margin="4">Stop</Button>
      <Button Name="ResetDB" Width="120" Margin="4">Reset DB</Button>
      <Button Name="OpenLogs" Width="100" Margin="4">View Logs</Button>
    </StackPanel>

    <TextBox Name="OutputBox" Grid.Row="1" AcceptsReturn="True" VerticalScrollBarVisibility="Auto" TextWrapping="Wrap" IsReadOnly="True"/>

    <StackPanel Orientation="Horizontal" Grid.Row="2" HorizontalAlignment="Right">
      <TextBlock VerticalAlignment="Center">Frontend:</TextBlock>
      <TextBlock Name="FrontendStatus" Width="120" Margin="6,0">Stopped</TextBlock>
      <TextBlock VerticalAlignment="Center">Backend:</TextBlock>
      <TextBlock Name="BackendStatus" Width="120" Margin="6,0">Stopped</TextBlock>
    </StackPanel>
  </Grid>
</Window>
"@

$reader = (New-Object System.Xml.XmlNodeReader $xaml)
$window = [Windows.Markup.XamlReader]::Load($reader)
$startBtn = $window.FindName('StartAll')
$stopBtn = $window.FindName('StopAll')
$resetBtn = $window.FindName('ResetDB')
$logsBtn = $window.FindName('OpenLogs')
$outputBox = $window.FindName('OutputBox')
$frontendStatus = $window.FindName('FrontendStatus')
$backendStatus = $window.FindName('BackendStatus')

# Variables to hold processes
$global:procBackend = $null
$global:procQueue = $null
$global:procFrontend = $null

function Append-Output([string]$s) { $outputBox.AppendText("$(Get-Date -Format 'HH:mm:ss') - $s`n"); $outputBox.ScrollToEnd() }

$startBtn.Add_Click({
    Append-Output 'Start clicked.'
    # Start Backend
    if (-not $global:procBackend -or $global:procBackend.HasExited) {
        Append-Output 'Starting backend...'
        $global:procBackend = Start-Process -FilePath 'powershell' -ArgumentList "-NoExit","-Command","cd `"$backendPath`"; if (!(Test-Path .env)) { copy .env.example .env }; php artisan key:generate; composer install; php artisan migrate --seed --force; php artisan serve --port=8000" -PassThru
        $backendStatus.Text = 'Running'
    } else { Append-Output 'Backend already running.' }

    # Start Queue
    if (-not $global:procQueue -or $global:procQueue.HasExited) {
        Append-Output 'Starting queue worker...'
        $global:procQueue = Start-Process -FilePath 'powershell' -ArgumentList "-NoExit","-Command","cd `"$backendPath`"; php artisan queue:work --sleep=3 --tries=3" -PassThru
    }

    # Start Frontend
    if (-not $global:procFrontend -or $global:procFrontend.HasExited) {
        Append-Output 'Starting frontend...'
        $global:procFrontend = Start-Process -FilePath 'powershell' -ArgumentList "-NoExit","-Command","cd `"$frontendPath`"; npm ci --prefer-offline --no-audit --silent; npm run dev" -PassThru
        $frontendStatus.Text = 'Running'
    }

    Start-Sleep -Seconds 2
    Append-Output 'Opening browser to http://localhost:3000'
    Start-Process 'http://localhost:3000'
})

$stopBtn.Add_Click({
    Append-Output 'Stop clicked. Stopping processes...'
    try { if ($global:procFrontend -and -not $global:procFrontend.HasExited) { $global:procFrontend.Kill(); $frontendStatus.Text='Stopped'; Append-Output 'Frontend stopped.' } } catch {}
    try { if ($global:procQueue -and -not $global:procQueue.HasExited) { $global:procQueue.Kill(); Append-Output 'Queue stopped.' } } catch {}
    try { if ($global:procBackend -and -not $global:procBackend.HasExited) { $global:procBackend.Kill(); $backendStatus.Text='Stopped'; Append-Output 'Backend stopped.' } } catch {}
})

$resetBtn.Add_Click({
    Append-Output 'Reset DB clicked. Running migrate:fresh --seed (destructive)!'
    Start-Process powershell -ArgumentList "-NoExit","-Command","cd `"$backendPath`"; php artisan migrate:fresh --seed --force" -Verb RunAs
})

$logsBtn.Add_Click({
    Append-Output 'Opening storage/logs/laravel.log in notepad (if exists)'
    $log = Join-Path $backendPath 'storage\logs\laravel.log'
    if (Test-Path $log) { Start-Process notepad $log } else { Append-Output 'No log file found yet.' }
})

# Show the window
$window.ShowDialog() | Out-Null
