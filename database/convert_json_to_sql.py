import json

json_file = 'c:/laragon/www/crm360/documentos/listado_empresas_filtrado.json'
sql_file = 'c:/laragon/www/crm360/database/import_companies.sql'

with open(json_file, 'r', encoding='utf-8') as f:
    data = json.load(f)

with open(sql_file, 'w', encoding='utf-8') as out:
    out.write('USE crm360;\n\n')
    for row in data:
        ruc = str(row.get('ruc', '')).replace("'", "''")
        business = str(row.get('business_name', '')).replace("'", "''")
        trade = str(row.get('trade_name', '')).replace("'", "''")
        address = str(row.get('parent_address', '')).replace("'", "''")
        mobile = str(row.get('mobile', '')).replace("'", "''")
        category = str(row.get('category_company', '')).replace("'", "''")
        date = str(row.get('date_creation', ''))
        
        sql = f"INSERT IGNORE INTO `companies` (`ruc`, `business_name`, `trade_name`, `address`, `mobile`, `category`, `creation_date`) VALUES ('{ruc}', '{business}', '{trade}', '{address}', '{mobile}', '{category}', '{date}');\n"
        out.write(sql)

print(f"Successfully generated {sql_file}")
