param(
    [ValidateSet('Check','Login','Update')]
    [string]$Action = 'Check',
    [int]$DebugPort = 9222
)

$ErrorActionPreference = 'Stop'
$ProfilePath = Join-Path $env:LOCALAPPDATA 'Muasamcong-CDP-Profile'
$PortalUrl = 'https://muasamcong.mpi.gov.vn/web/guest/profile-info'
$CookieUrl = 'https://muasamcong.mpi.gov.vn/o/egp-portal-personal-page/services/get-list-notify-contractor-join'

function Get-ChromePath {
    $candidates = @(
        "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
        "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
        "$env:LOCALAPPDATA\Google\Chrome\Application\chrome.exe"
    )

    foreach ($candidate in $candidates) {
        if ($candidate -and (Test-Path $candidate)) {
            return $candidate
        }
    }

    throw 'Khong tim thay Google Chrome.'
}

function Receive-CdpMessage {
    param([System.Net.WebSockets.ClientWebSocket]$Socket)

    $buffer = New-Object byte[] 65536
    $stream = New-Object System.IO.MemoryStream

    try {
        do {
            $segment = New-Object System.ArraySegment[byte] -ArgumentList (, $buffer)
            $result = $Socket.ReceiveAsync($segment, [Threading.CancellationToken]::None).GetAwaiter().GetResult()

            if ($result.MessageType -eq [System.Net.WebSockets.WebSocketMessageType]::Close) {
                throw 'CDP WebSocket da dong ket noi.'
            }

            $stream.Write($buffer, 0, $result.Count)
        } while (-not $result.EndOfMessage)

        return [Text.Encoding]::UTF8.GetString($stream.ToArray())
    }
    finally {
        $stream.Dispose()
    }
}

function Get-PersonalPageCookieHeader {
    $versionUrl = "http://127.0.0.1:$DebugPort/json/version"

    try {
        $version = Invoke-RestMethod -Uri $versionUrl -Method Get -TimeoutSec 5
    }
    catch {
        throw 'Khong ket noi duoc Chrome rieng. Hay chon menu 2 de mo/dang nhap Mua sam cong.'
    }

    $wsUrl = [string]$version.webSocketDebuggerUrl
    if (-not $wsUrl) {
        throw 'Chrome CDP khong tra webSocketDebuggerUrl.'
    }

    $wsUrl = $wsUrl -replace '^ws://localhost:', 'ws://127.0.0.1:'
    $wsUrl = $wsUrl -replace '^ws://\[::1\]:', 'ws://127.0.0.1:'

    $socket = New-Object System.Net.WebSockets.ClientWebSocket

    try {
        $socket.Options.SetRequestHeader('Origin', "http://127.0.0.1:$DebugPort")
        [void]$socket.ConnectAsync([Uri]$wsUrl, [Threading.CancellationToken]::None).GetAwaiter().GetResult()

        $request = @{
            id = 1
            method = 'Network.getCookies'
            params = @{ urls = @($CookieUrl) }
        } | ConvertTo-Json -Depth 5 -Compress

        $bytes = [Text.Encoding]::UTF8.GetBytes($request)
        $segment = New-Object System.ArraySegment[byte] -ArgumentList (, $bytes)
        [void]$socket.SendAsync(
            $segment,
            [System.Net.WebSockets.WebSocketMessageType]::Text,
            $true,
            [Threading.CancellationToken]::None
        ).GetAwaiter().GetResult()

        $response = $null
        do {
            $message = Receive-CdpMessage -Socket $socket | ConvertFrom-Json
            if ($message.id -eq 1) {
                $response = $message
            }
        } while ($null -eq $response)

        if ($response.error) {
            throw "CDP Network.getCookies loi: $($response.error.message)"
        }

        $cookies = @($response.result.cookies)
        if ($cookies.Count -eq 0) {
            throw 'Khong co Cookie Personal Page cho URL can dung.'
        }

        $jsession = @($cookies | Where-Object { $_.name -eq 'JSESSIONID' })
        if ($jsession.Count -eq 0) {
            throw 'Chua co JSESSIONID Personal Page. Hay dang nhap Mua sam cong.'
        }

        return ($cookies | ForEach-Object { "$($_.name)=$($_.value)" }) -join '; '
    }
    finally {
        if ($socket.State -eq [System.Net.WebSockets.WebSocketState]::Open) {
            [void]$socket.CloseAsync(
                [System.Net.WebSockets.WebSocketCloseStatus]::NormalClosure,
                'done',
                [Threading.CancellationToken]::None
            ).GetAwaiter().GetResult()
        }
        $socket.Dispose()
    }
}

function Open-MuasamcongChrome {
    $chrome = Get-ChromePath
    Start-Process -FilePath $chrome -ArgumentList @(
        '--remote-debugging-address=127.0.0.1',
        "--remote-debugging-port=$DebugPort",
        "--remote-allow-origins=http://127.0.0.1:$DebugPort",
        "--user-data-dir=$ProfilePath",
        $PortalUrl
    )

    Write-Host 'Da mo Chrome rieng cho Mua sam cong.' -ForegroundColor Green
    Write-Host 'Hay dang nhap binh thuong. CAPTCHA/OTP neu co van thuc hien bang tay.'
}

function Update-RemoteSession {
    $cookie = Get-PersonalPageCookieHeader
    Write-Host 'Da tim thay Cookie Personal Page hop le ve cau truc (khong hien thi gia tri).' -ForegroundColor Green
    Write-Host
    Write-Host 'Tao link tai: Admin > Muasamcong > Config > Personal Page Session > Tao link cap nhat Windows.' -ForegroundColor Cyan
    $updateLink = Read-Host 'Dan LINK UPDATE vao day'

    if ([string]::IsNullOrWhiteSpace($updateLink)) {
        throw 'Chua nhap link update.'
    }

    $uri = [Uri]$updateLink
    if ($uri.Scheme -ne 'https') {
        throw 'Link update bat buoc phai dung HTTPS.'
    }

    $token = $uri.Fragment.TrimStart('#')
    if ([string]::IsNullOrWhiteSpace($token)) {
        throw 'Link update khong co ma dung mot lan.'
    }

    $endpoint = "$($uri.Scheme)://$($uri.Authority)/api/muasamcong/update-cookie"
    $body = @{ cookie = $cookie } | ConvertTo-Json -Compress

    try {
        $result = Invoke-RestMethod -Uri $endpoint -Method Post -ContentType 'application/json' -Headers @{
            'X-Muasamcong-Import-Token' = $token
            'Accept' = 'application/json'
        } -Body $body -TimeoutSec 60
    }
    finally {
        $cookie = $null
        $body = $null
        $token = $null
    }

    Write-Host
    Write-Host '[OK] Server da cap nhat va xac minh Session.' -ForegroundColor Green
    if ($null -ne $result.total) {
        Write-Host "So goi tra ve khi kiem tra: $($result.total)"
    }

    $configLink = "$($uri.Scheme)://$($uri.Authority)/admin/muasamcong/config"
    Write-Host "Link kiem tra tren UI: $configLink" -ForegroundColor Cyan
}

switch ($Action) {
    'Check' {
        $cookie = Get-PersonalPageCookieHeader
        $cookie = $null
        Write-Host '[OK] Da co Cookie/JSESSIONID Personal Page.' -ForegroundColor Green
    }
    'Login' {
        Open-MuasamcongChrome
    }
    'Update' {
        Update-RemoteSession
    }
}
