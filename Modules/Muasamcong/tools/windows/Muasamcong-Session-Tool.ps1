param(
    [ValidateSet('Check','Refresh','Login','Update')]
    [string]$Action = 'Check',
    [int]$DebugPort = 9222
)

$ErrorActionPreference = 'Stop'
$ProfilePath = Join-Path $env:LOCALAPPDATA 'Muasamcong-CDP-Profile'
$PortalUrl = 'https://muasamcong.mpi.gov.vn/web/guest/profile-info?p_p_id=egpportalpersonalpage_WAR_egpportalpersonalpage&p_p_lifecycle=0&p_p_state=normal&p_p_mode=view&_egpportalpersonalpage_WAR_egpportalpersonalpage_render=personalUrl&menu=tender-pakage-list'
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

function Test-CookieAppliesToUrl {
    param(
        $Cookie,
        [Uri]$Uri
    )

    $cookieDomain = ([string]$Cookie.domain).TrimStart('.').ToLowerInvariant()
    $targetHost = $Uri.Host.ToLowerInvariant()

    $domainMatches = $targetHost -eq $cookieDomain -or $targetHost.EndsWith('.' + $cookieDomain)
    if (-not $domainMatches) {
        return $false
    }

    $cookiePath = [string]$Cookie.path
    if ([string]::IsNullOrWhiteSpace($cookiePath)) {
        $cookiePath = '/'
    }

    if (-not $Uri.AbsolutePath.StartsWith($cookiePath, [StringComparison]::Ordinal)) {
        return $false
    }

    if ([bool]$Cookie.secure -and $Uri.Scheme -ne 'https') {
        return $false
    }

    $expires = [double]$Cookie.expires
    if ($expires -gt 0) {
        $nowUnix = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
        if ($expires -le $nowUnix) {
            return $false
        }
    }

    return $true
}

function Get-PersonalPageCookies {
    $versionUrl = "http://127.0.0.1:$DebugPort/json/version"

    try {
        $version = Invoke-RestMethod -Uri $versionUrl -Method Get -TimeoutSec 5
    }
    catch {
        throw 'Khong ket noi duoc Chrome rieng. Hay chon menu Dang nhap de mo Chrome Mua sam cong.'
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
            method = 'Storage.getCookies'
        } | ConvertTo-Json -Compress

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

        $targetUri = [Uri]$CookieUrl
        $cookies = @($response.result.cookies) | Where-Object {
            Test-CookieAppliesToUrl -Cookie $_ -Uri $targetUri
        }

        if ($cookies.Count -eq 0) {
            throw 'Khong co Cookie Personal Page cho URL can dung.'
        }

        return @($cookies)
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

function Get-PersonalPageCookieHeader {
    $cookies = Get-PersonalPageCookies
    $jsession = @($cookies | Where-Object { $_.name -eq 'JSESSIONID' })

    if ($jsession.Count -eq 0) {
        throw 'Chua co JSESSIONID Personal Page. Hay dang nhap Mua sam cong.'
    }

    $ordered = $cookies | Sort-Object @{ Expression = { ([string]$_.path).Length }; Descending = $true }, name
    return ($ordered | ForEach-Object { "$($_.name)=$($_.value)" }) -join '; '
}

function Show-LocalSessionSummary {
    $cookies = Get-PersonalPageCookies
    $names = @($cookies | ForEach-Object { [string]$_.name })
    $jsessionCount = @($cookies | Where-Object { $_.name -eq 'JSESSIONID' }).Count
    $keycloakIdentity = @($cookies | Where-Object { $_.name -eq 'KEYCLOAK_IDENTITY' } | Select-Object -First 1)

    Write-Host '[OK] Chrome dang co Cookie Personal Page.' -ForegroundColor Green
    Write-Host "So cookie ap dung: $($cookies.Count)"
    Write-Host "So JSESSIONID: $jsessionCount"

    if ($keycloakIdentity.Count -gt 0 -and [double]$keycloakIdentity[0].expires -gt 0) {
        $expiry = [DateTimeOffset]::FromUnixTimeSeconds([int64][double]$keycloakIdentity[0].expires).ToLocalTime()
        Write-Host "KEYCLOAK_IDENTITY het han theo Chrome: $($expiry.ToString('dd/MM/yyyy HH:mm:ss zzz'))"
    }
    else {
        Write-Host 'KEYCLOAK_IDENTITY: khong co expiry doc duoc tu Chrome.'
    }

    if ($names -contains 'KEYCLOAK_SESSION') {
        Write-Host 'SSO Keycloak: co dau hieu con session trong Chrome.' -ForegroundColor Cyan
    }

    Write-Host 'Luu y: Cookie ton tai tren Chrome khong dam bao API Personal Page con hop le; Server se xac minh khi Update.' -ForegroundColor Yellow
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
    Write-Host 'Neu SSO con han, Personal Page co the vao thang va tu tao portal session moi.' -ForegroundColor Cyan
    Write-Host 'Neu bi dua ve trang dang nhap, hay dang nhap binh thuong. CAPTCHA/OTP neu co van thuc hien bang tay.'
}

function Wait-ForChromeCdp {
    param([int]$TimeoutSeconds = 20)

    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    do {
        try {
            $null = Invoke-RestMethod -Uri "http://127.0.0.1:$DebugPort/json/version" -Method Get -TimeoutSec 2
            return
        }
        catch {
            Start-Sleep -Milliseconds 750
        }
    } while ((Get-Date) -lt $deadline)

    throw 'Chrome da mo nhung CDP chua san sang. Hay cho vai giay roi thu lai.'
}

function Read-UpdateLink {
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

    return [PSCustomObject]@{
        Uri = $uri
        Token = $token
    }
}

function Send-RemoteSession {
    param(
        [string]$Cookie,
        [Uri]$Uri,
        [string]$Token
    )

    $endpoint = "$($Uri.Scheme)://$($Uri.Authority)/api/muasamcong/update-cookie"
    $body = @{ cookie = $Cookie } | ConvertTo-Json -Compress

    try {
        return Invoke-RestMethod -Uri $endpoint -Method Post -ContentType 'application/json' -Headers @{
            'X-Muasamcong-Import-Token' = $Token
            'Accept' = 'application/json'
        } -Body $body -TimeoutSec 60
    }
    finally {
        $body = $null
    }
}

function Update-RemoteSession {
    $link = Read-UpdateLink
    $cookie = Get-PersonalPageCookieHeader

    try {
        Write-Host 'Da tim thay Cookie Personal Page (khong hien thi gia tri).' -ForegroundColor Green
        $result = Send-RemoteSession -Cookie $cookie -Uri $link.Uri -Token $link.Token

        Write-Host
        Write-Host '[OK] Server da cap nhat va xac minh Session.' -ForegroundColor Green
        if ($null -ne $result.total) {
            Write-Host "So goi tra ve khi kiem tra: $($result.total)"
        }

        $configLink = "$($link.Uri.Scheme)://$($link.Uri.Authority)/admin/muasamcong/config"
        Write-Host "Link kiem tra tren UI: $configLink" -ForegroundColor Cyan
    }
    finally {
        $cookie = $null
        $link.Token = $null
    }
}

function Refresh-RemoteSession {
    $link = Read-UpdateLink

    Write-Host
    Write-Host '[1/4] Dang mo Personal Page bang Chrome profile rieng...' -ForegroundColor Cyan
    Open-MuasamcongChrome
    Wait-ForChromeCdp -TimeoutSeconds 20

    Write-Host '[2/4] Cho portal/SSO lam moi session...' -ForegroundColor Cyan
    Start-Sleep -Seconds 5

    Write-Host '[3/4] Dang lay Cookie moi tu Chrome...' -ForegroundColor Cyan
    $cookie = $null
    try {
        $cookie = Get-PersonalPageCookieHeader
    }
    catch {
        Write-Host
        Write-Host 'Chua lay duoc portal session hop le.' -ForegroundColor Yellow
        Write-Host 'Neu Chrome dang hien trang dang nhap, hay dang nhap xong roi chon menu Lam moi tu dong lai.' -ForegroundColor Yellow
        throw
    }

    try {
        Write-Host '[4/4] Dang gui Session moi ve server va xac minh API...' -ForegroundColor Cyan
        $result = Send-RemoteSession -Cookie $cookie -Uri $link.Uri -Token $link.Token

        Write-Host
        Write-Host '[OK] Lam moi Session thanh cong.' -ForegroundColor Green
        if ($null -ne $result.total) {
            Write-Host "API lich su nha thau phan hoi: $($result.total) goi."
        }
        Write-Host 'Lan sau neu SSO con han, chi can tao link moi va chon Lam moi tu dong.' -ForegroundColor Green
    }
    catch {
        Write-Host
        Write-Host 'Server khong xac minh duoc Session vua lay.' -ForegroundColor Yellow
        Write-Host 'Neu Chrome vao Mua sam cong ma khong can nhap mat khau, hay doi 3-5 giay va thu lai. Neu bi dang xuat, hay dang nhap lai.' -ForegroundColor Yellow
        throw
    }
    finally {
        $cookie = $null
        $link.Token = $null
    }
}

switch ($Action) {
    'Check' {
        Show-LocalSessionSummary
    }
    'Refresh' {
        Refresh-RemoteSession
    }
    'Login' {
        Open-MuasamcongChrome
    }
    'Update' {
        Update-RemoteSession
    }
}
