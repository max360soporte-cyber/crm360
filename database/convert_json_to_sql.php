<?php

$json_file = 'c:/laragon/www/crm360/documentos/listado_empresas_filtrado.json';
$sql_file = 'c:/laragon/www/crm360/database/import_companies.sql';

$json_data = file_get_contents($json_file);
$data = json_decode($json_data, true);

if ($data === null) {
    die("Error decoding JSON\n");
}

$sql_content = "USE crm360;\n\n";

foreach ($data as $row) {
    $ruc = str_replace("'", "''", $row['ruc'] ?? '');
    $business = str_replace("'", "''", $row['business_name'] ?? '');
    $trade = str_replace("'", "''", $row['trade_name'] ?? '');
    $address = str_replace("'", "''", $row['parent_address'] ?? '');
    $mobile = str_replace("'", "''", $row['mobile'] ?? '');
    $category = str_replace("'", "''", $row['category_company'] ?? '');
    $date = $row['date_creation'] ?? '';

    $sql = "INSERT IGNORE INTO `companies` (`ruc`, `business_name`, `trade_name`, `address`, `mobile`, `category`, `creation_date`) VALUES ('$ruc', '$business', '$trade', '$address', '$mobile', '$category', '$date');\n";
    $sql_content .= $sql;
}

file_put_contents($sql_file, $sql_content);
echo "Successfully generated $sql_file\n";
