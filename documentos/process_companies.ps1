$inputPath = "c:\laragon\www\crm360\documentos\listado de empresa.txt"
$outputPathJson = "c:\laragon\www\crm360\documentos\listado_empresas_filtrado.json"
$outputPathMd = "c:\laragon\www\crm360\documentos\listado_empresas_filtrado.md"

if (-not (Test-Path $inputPath)) {
    Write-Error "Input file not found: $inputPath"
    exit 1
}

# Read content and skip header if present
$content = Get-Content -Path $inputPath -Raw -Encoding UTF8
$firstBracket = $content.IndexOf("[")
if ($firstBracket -ge 0) {
    $content = $content.Substring($firstBracket)
}

# Join concatenated arrays: replace "]\s*[" with ","
$content = $content -replace "\]\s*\[", ","

try {
    $data = $content | ConvertFrom-Json -ErrorAction Stop
} catch {
    Write-Warning "JSON parsing failed. Attempting to clean up stray characters..."
    # Basic cleanup if there are any trailing characters or missing brackets
    $content = $content.Trim()
    if (-not $content.StartsWith("[")) { $content = "[" + $content }
    if (-not $content.EndsWith("]")) { $content = $content + "]" }
    $data = $content | ConvertFrom-Json
}

$results = New-Object System.Collections.Generic.List[PSCustomObject]
foreach ($item in $data) {
    if ($null -eq $item -or -not $item.PSObject.Properties.Name -contains "ruc") { continue }
    
    $cat = ""
    if ($null -ne $item.category_company) {
        if ($item.category_company -is [PSCustomObject] -and $item.category_company.PSObject.Properties.Name -contains "name") {
            $cat = $item.category_company.name
        } else {
            $cat = $item.category_company.ToString()
        }
    }

    $obj = [PSCustomObject]@{
        id = [int]$item.id
        ruc = [string]$item.ruc
        business_name = [string]$item.business_name
        trade_name = [string]$item.trade_name
        parent_address = [string]$item.parent_address
        mobile = [string]$item.mobile
        category_company = $cat
        date_creation = [string]$item.date_creation
    }
    $results.Add($obj)
}

# Save JSON
$results | ConvertTo-Json -Depth 5 | Set-Content -Path $outputPathJson -Encoding UTF8

# Generate Markdown
$md = "# Listado de Empresas Filtrado`n`n"
$md += "| ID | RUC | Raz$([char]243)n Social | Nombre Comercial | Direcci$([char]243)n | Celular | Categor$([char]237)a | Fecha Creaci$([char]243)n |`n"
$md += "| --- | --- | --- | --- | --- | --- | --- | --- |`n"
foreach ($item in $results) {
    $id = if ($item.id) { $item.id } else { "" }
    $ruc = if ($item.ruc) { $item.ruc -replace '\|', '/' } else { "" }
    $bn = if ($item.business_name) { $item.business_name -replace '\|', '/' } else { "" }
    $tn = if ($item.trade_name) { $item.trade_name -replace '\|', '/' } else { "" }
    $addr = if ($item.parent_address) { $item.parent_address -replace '\|', '/' -replace '[\r\n]', ' ' } else { "" }
    $mob = if ($item.mobile) { $item.mobile -replace '\|', '/' } else { "" }
    $cat = if ($item.category_company) { $item.category_company -replace '\|', '/' } else { "" }
    $date = if ($item.date_creation) { $item.date_creation -replace '\|', '/' } else { "" }
    $md += "| $id | $ruc | $bn | $tn | $addr | $mob | $cat | $date |`n"
}
$md | Set-Content -Path $outputPathMd -Encoding UTF8

Write-Output "Successfully processed $($results.Count) companies."
