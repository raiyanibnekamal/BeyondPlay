# Inject arena-ui.css/js, remove preloader button, fix page titles.
$ErrorActionPreference = "Stop"
$root = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
Set-Location $root

$cssTag = '<link rel="stylesheet" href="assets/css/arena-ui.css">'
$cssAfter = '<link rel="stylesheet" href="assets/css/style.css">'
$jsTag = '<script src="assets/js/arena-ui.js"></script>'
$jsBefore = '<script src="assets/js/main.js"></script>'
$preloaderBtn = '<button class="th-btn preloaderCls">CANCEL PRELOADER</button>'

$titleFixes = @(
    @{ Pattern = 'Arena Ã¢â‚¬â€ Esports Tournament Platform \| '; Replacement = 'Arena | ' }
    @{ Pattern = 'Arena â€” Esports Tournament Platform \| '; Replacement = 'Arena | ' }
    @{ Pattern = 'Arena Ã¢â‚¬â€ '; Replacement = 'Arena — ' }
    @{ Pattern = 'Arena â€” '; Replacement = 'Arena — ' }
)

$fixed = 0
Get-ChildItem -Path $root -Filter *.html | ForEach-Object {
    $c = [IO.File]::ReadAllText($_.FullName)
    $orig = $c

    if ($c.Contains($preloaderBtn)) {
        $c = $c.Replace($preloaderBtn, '')
    }
    if (-not $c.Contains('arena-ui.css')) {
        $c = $c.Replace($cssAfter, "$cssAfter$cssTag")
    }
    if (-not $c.Contains('arena-ui.js')) {
        if ($c.Contains($jsBefore)) {
            $c = $c.Replace($jsBefore, "$jsTag$jsBefore")
        } else {
            $c = $c.Replace('</body>', "$jsTag</body>")
        }
    }
    foreach ($fix in $titleFixes) {
        $c = $c.Replace($fix.Pattern, $fix.Replacement)
    }

    if ($c -ne $orig) {
        [IO.File]::WriteAllText($_.FullName, $c)
        $fixed++
        Write-Host "Updated: $($_.Name)"
    }
}
Write-Host "Done. $fixed HTML files updated."
