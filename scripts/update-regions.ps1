$ErrorActionPreference = 'Stop'
$baseUrl = 'https://www.emsifa.com/api-wilayah-indonesia/v2'
$provinces = (Invoke-RestMethod -Uri "$baseUrl/provinces.json" -TimeoutSec 30).data
if ($provinces.Count -ne 38) {
    throw "Expected 38 provinces, received $($provinces.Count)."
}

$regions = [ordered]@{}
$cityCount = 0
foreach ($province in $provinces) {
    $cities = (Invoke-RestMethod -Uri "$baseUrl/regencies/$($province.id).json" -TimeoutSec 30).data
    if (-not $cities) {
        throw "No cities returned for $($province.name)."
    }
    $regions[$province.name] = @($cities | ForEach-Object { $_.name } | Sort-Object)
    $cityCount += $cities.Count
}
if ($cityCount -ne 514) {
    throw "Expected 514 cities/regencies, received $cityCount."
}

$target = Join-Path $PSScriptRoot '..\resources\data\indonesia-regions.json'
$json = $regions | ConvertTo-Json -Depth 4
[System.IO.File]::WriteAllText([System.IO.Path]::GetFullPath($target), $json, [System.Text.UTF8Encoding]::new($false))
Write-Output "Saved $($provinces.Count) provinces and $cityCount cities/regencies to $target"
