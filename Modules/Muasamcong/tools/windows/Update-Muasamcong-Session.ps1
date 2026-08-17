param(
    [int]$DebugPort = 9222,
    [string]$WslProjectPath = "/var/www/source-laravel12"
)

$ErrorActionPreference = "Stop"

function Receive-CdpMessage {
    param(
        [System.Net.WebSockets.ClientWebSocket]$Socket
    )

    $buffer = New-Object byte[] 65536
    $stream = New-Object System.IO.MemoryStream

    try {
        do {
            $segment = New-Object System.ArraySegment[byte] -ArgumentList (, $buffer)
            $result = $Socket.ReceiveAsync($segment, [Threading.CancellationToken]::None).GetAwaiter().GetResult()

            if ($result.MessageType -eq [System.Net.WebSockets.WebSocketMessageType]::Close) {
                throw "CDP WebSocket da dong ket noi."
            }

            $stream.Write($buffer, 0, $result.Count)
        } while (-not $result.EndOfMessage)

        return [Text.Encoding]::UTF8.GetString($stream.ToArray())
    }
    finally {
        $stream.Dispose()
    }
}

function Test-CookiePathMatch {
    param(
        [string]$RequestPath,
        [string]$CookiePath
    )

    if ([string]::IsNullOrWhiteSpace($CookiePath)) {
        $CookiePath = "/"
    }

    if ($RequestPath -eq $CookiePath) {
        return $true
    }

    if (-not $RequestPath.StartsWith($CookiePath, [StringComparison]::Ordinal)) {
        return $false
    }

    if ($CookiePath.EndsWith("/")) {
        return $true
    }

    return $RequestPath.Length -gt $CookiePath.Length -and $RequestPath[$CookiePath.Length] -eq '/'
}

function Get-MuasamcongCookieHeader {
    param([int]$Port)

    $versionUrl = "http://127.0.0.1:$Port/json/version"
    $targetHost = "muasamcong.mpi.gov.vn"
    $targetPath = "/o/egp-portal-personal-page/services/get-list-notify-contractor-join"

    try {
        $version = Invoke-RestMethod -Uri $versionUrl -Method Get -TimeoutSec 5
    }
    catch {
        throw "Khong ket noi duoc Chrome CDP tai $versionUrl. Hay chay Open-Muasamcong-Chrome.bat truoc."
    }

    if (-not $version.webSocketDebuggerUrl) {
        throw "Chrome CDP khong tra webSocketDebuggerUrl."
    }

    $wsUrl = [string]$version.webSocketDebuggerUrl
    $wsUrl = $wsUrl -replace '^ws://localhost:', 'ws://127.0.0.1:'
    $wsUrl = $wsUrl -replace '^ws://\[::1\]:', 'ws://127.0.0.1:'

    $socket = New-Object System.Net.WebSockets.ClientWebSocket

    try {
        $socket.Options.SetRequestHeader("Origin", "http://127.0.0.1:$Port")
        [void]$socket.ConnectAsync([Uri]$wsUrl, [Threading.CancellationToken]::None).GetAwaiter().GetResult()

        $request = @{ id = 1; method = "Storage.getCookies" } | ConvertTo-Json -Compress
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
            throw "CDP Storage.getCookies loi: $($response.error.message)"
        }

        $nowUnix = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
        $cookies = @($response.result.cookies) | Where-Object {
            $domain = ([string]$_.domain).TrimStart('.')
            $cookiePath = [string]$_.path
            $expires = [double]$_.expires
            $domainMatches = $domain -eq $targetHost
            $pathMatches = Test-CookiePathMatch -RequestPath $targetPath -CookiePath $cookiePath
            $notExpired = $expires -le 0 -or $expires -gt $nowUnix

            $domainMatches -and $pathMatches -and $notExpired
        }

        if ($cookies.Count -eq 0) {
            throw "Khong tim thay cookie ap dung cho API Personal Page. Hay mo trang Mua sam cong va dang nhap."
        }

        $jsessions = @($cookies | Where-Object { $_.name -eq "JSESSIONID" })
        if ($jsessions.Count -eq 0) {
            throw "Khong tim thay JSESSIONID cua Personal Page. Hay dang nhap Mua sam cong tren Chrome rieng truoc."
        }

        # Cookie header cua trinh duyet uu tien cookie co path cu the hon truoc.
        # Loc theo targetPath o tren se loai JSESSIONID cua Keycloak (/security/...) khoi API /o/....
        $ordered = $cookies | Sort-Object @{ Expression = { ([string]$_.path).Length }; Descending = $true }, name
        return ($ordered | ForEach-Object { "$($_.name)=$($_.value)" }) -join "; "
    }
    finally {
        if ($socket.State -eq [System.Net.WebSockets.WebSocketState]::Open) {
            [void]$socket.CloseAsync(
                [System.Net.WebSockets.WebSocketCloseStatus]::NormalClosure,
                "done",
                [Threading.CancellationToken]::None
            ).GetAwaiter().GetResult()
        }
        $socket.Dispose()
    }
}

Write-Host "[1/3] Dang doc session tu Chrome rieng..." -ForegroundColor Cyan
$cookieHeader = Get-MuasamcongCookieHeader -Port $DebugPort

Write-Host "[2/3] Da tim thay session Personal Page phu hop (khong hien thi gia tri cookie)." -ForegroundColor Green

if ($WslProjectPath -notmatch '^/[A-Za-z0-9_./-]+$') {
    throw "WSL project path khong hop le."
}

$command = "cd $WslProjectPath && php artisan msc:import-personal-session --stdin --test"

Write-Host "[3/3] Dang luu session ma hoa vao Laravel va kiem tra API..." -ForegroundColor Cyan

$cookieHeader | & wsl.exe bash -lc $command
$exitCode = $LASTEXITCODE

$cookieHeader = $null

if ($exitCode -ne 0) {
    throw "Laravel khong chap nhan/khong xac minh duoc session. Hay dang nhap lai tren Chrome rieng roi thu lai."
}

Write-Host "Hoan tat. Personal Page Session da duoc cap nhat." -ForegroundColor Green
