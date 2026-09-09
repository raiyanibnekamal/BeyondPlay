# Fix Arena frontend interconnectivity: footer links, pagination, scripts, auth guard.
$ErrorActionPreference = "Stop"
$root = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
Set-Location $root

$usefulOld = '<li><a href="blog.html"><i class="far fa-angle-right"></i>Tournaments</a></li><li><a href="blog.html"><i class="far fa-angle-right"></i>Shop</a></li><li><a href="blog.html"><i class="far fa-angle-right"></i>Tournaments</a></li><li><a href="blog.html"><i class="far fa-angle-right"></i>Rankings</a></li><li><a href="blog.html"><i class="far fa-angle-right"></i>Community</a></li><li><a href="blog.html"><i class="far fa-angle-right"></i>Achievements</a></li>'
$usefulNew = '<li><a href="tournament.html"><i class="far fa-angle-right"></i>Tournaments</a></li><li><a href="shop.html"><i class="far fa-angle-right"></i>Shop</a></li><li><a href="game.html"><i class="far fa-angle-right"></i>Games</a></li><li><a href="point-table.html"><i class="far fa-angle-right"></i>Rankings</a></li><li><a href="friends.html"><i class="far fa-angle-right"></i>Community</a></li><li><a href="achievements.html"><i class="far fa-angle-right"></i>Achievements</a></li>'

$supportFixes = @(
    @{ Old = 'href="contact.html"><i class="far fa-angle-right"></i> Privacy Policy'; New = 'href="terms.html#privacy"><i class="far fa-angle-right"></i> Privacy Policy' },
    @{ Old = 'href="contact.html"><i class="far fa-angle-right"></i> Blog'; New = 'href="blog.html"><i class="far fa-angle-right"></i> Blog' },
    @{ Old = 'href="contact.html"><i class="far fa-angle-right"></i> Live Events'; New = 'href="tournament.html"><i class="far fa-angle-right"></i> Live Events' },
    @{ Old = 'href="contact.html"><i class="far fa-angle-right"></i> Match Details'; New = 'href="bracket.html"><i class="far fa-angle-right"></i> Match Details' }
)

$authGuardPages = @(
    'wishlist.html', 'checkout.html', 'bracket-prediction.html',
    'dispute.html', 'checkin.html', 'suggest-game.html',
    'my-profile.html', 'my-tournaments.html', 'my-orders.html',
    'my-notifications.html', 'achievements.html', 'friends.html',
    'activity-feed.html', 'stats.html', 'tournament-history.html', 'head-to-head.html'
)

$authGuardTag = '<script src="assets/js/arena-auth-guard.js"></script>'
$apiTag = '<script src="assets/js/api.js"></script>'

function Dedupe-ScriptTags([string]$html) {
    $pattern = '<script src="([^"]+)"></script>'
    $seen = @{}
    $ordered = New-Object System.Collections.Generic.List[string]
    foreach ($m in [regex]::Matches($html, $pattern)) {
        $src = $m.Groups[1].Value
        if (-not $seen.ContainsKey($src)) {
            $seen[$src] = $true
            [void]$ordered.Add($m.Value)
        }
    }
    if ($ordered.Count -eq 0) { return $html }
    $block = ($ordered -join '')
    return [regex]::Replace($html, '(?:<script src="[^"]+"></script>)+', $block, 1)
}

$fixed = 0
Get-ChildItem -Path $root -Filter *.html | ForEach-Object {
    $name = $_.Name
    $c = [IO.File]::ReadAllText($_.FullName)
    $orig = $c

    if ($c.Contains($usefulOld)) {
        $c = $c.Replace($usefulOld, $usefulNew)
    }
    foreach ($fix in $supportFixes) {
        if ($c.Contains($fix.Old)) {
            $c = $c.Replace($fix.Old, $fix.New)
        }
    }

    if ($name -in @('shop.html', 'team.html', 'game.html', 'tournament.html')) {
        if ($c -match '(<div class="th-pagination[^>]*>.*?</div>)') {
            $block = $matches[1] -replace 'blog\.html', $name
            $c = $c.Replace($matches[1], $block)
        }
    }

    if ($name -eq 'checkout.html') {
        $c = $c.Replace('href="#" class="showlogin">Click here to login', 'href="login.html?redirect=checkout.html" class="showlogin">Click here to login')
        $c = $c.Replace('href="#">Lost your password?', 'href="forgot-password.html">Lost your password?')
    }

    if ($name -eq 'my-orders.html') {
        $c = $c.Replace('href="#" class="link-btn style2">View Details', 'href="shop.html" class="link-btn style2">View Shop')
    }

    if ([regex]::Matches($c, 'assets/js/config\.js').Count -gt 1) {
        $c = Dedupe-ScriptTags $c
        $wrongArena = '<script src="assets/js/config.js"></script><script src="assets/js/api.js"></script><script src="assets/js/arena-connect.js"></script><script src="assets/js/cart.js"></script><script src="assets/js/arena-escape.js"></script>'
        $rightArena = '<script src="assets/js/config.js"></script><script src="assets/js/api.js"></script><script src="assets/js/arena-escape.js"></script><script src="assets/js/arena-connect.js"></script><script src="assets/js/cart.js"></script>'
        if ($c.Contains($wrongArena)) {
            $c = $c.Replace($wrongArena, $rightArena)
        }
    }

    if ($authGuardPages -contains $name -and $c -notmatch 'arena-auth-guard\.js') {
        $c = $c.Replace($apiTag, ($apiTag + $authGuardTag))
    }

    if ($c -ne $orig) {
        [IO.File]::WriteAllText($_.FullName, $c)
        $fixed++
        Write-Host "Updated $name"
    }
}

Write-Host "Done. $fixed HTML file(s) updated."
