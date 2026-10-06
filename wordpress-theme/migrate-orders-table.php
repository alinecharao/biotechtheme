<?php
/**
 * Migração da tabela cursos_orders
 * 
 * COMO USAR:
 * 1. Acesse: seusite.com/wp-content/themes/biotech-theme2/migrate-orders-table.php
 * 2. A migração será executada automaticamente
 * 3. DELETE este arquivo após executar!
 * 
 * @package VetCursos
 */

// Carregar WordPress
$wp_load_paths = [
    dirname(__FILE__) . '/../../../wp-load.php',
    dirname(__FILE__) . '/../../../../wp-load.php',
    dirname(__FILE__) . '/../../../../../wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('Erro: Não foi possível carregar o WordPress. Verifique o caminho do arquivo.');
}

// Verificar se é administrador
if (!current_user_can('manage_options')) {
    wp_die('Acesso negado. Você precisa estar logado como administrador.');
}

global $wpdb;
$table_name = $wpdb->prefix . 'cursos_orders';

// Verificar se a tabela existe
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if (!$table_exists) {
    wp_die("Erro: A tabela '$table_name' não existe. Execute a ativação do plugin VetCursos Pedidos primeiro.");
}

// Colunas que precisam existir
$required_columns = [
    'subtotal' => "DECIMAL(10,2) DEFAULT 0.00",
    'discount' => "DECIMAL(10,2) DEFAULT 0.00",
    'coupon_code' => "VARCHAR(50) DEFAULT NULL",
    'coupon_id' => "BIGINT DEFAULT NULL",
    'pix_discount' => "DECIMAL(10,2) DEFAULT 0.00",
    'student_discount' => "DECIMAL(10,2) DEFAULT 0.00",
    'is_student' => "TINYINT(1) DEFAULT 0",
];

// Obter colunas existentes
$existing_columns = [];
$columns_result = $wpdb->get_results("DESCRIBE $table_name");
foreach ($columns_result as $column) {
    $existing_columns[] = $column->Field;
}

// Resultados da migração
$results = [];
$errors = [];

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Migração - VetCursos Orders</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1d2327; margin-bottom: 20px; }
        .success { color: #00a32a; background: #edfaef; padding: 10px 15px; border-radius: 4px; margin: 5px 0; }
        .warning { color: #996800; background: #fcf9e8; padding: 10px 15px; border-radius: 4px; margin: 5px 0; }
        .error { color: #d63638; background: #fcf0f1; padding: 10px 15px; border-radius: 4px; margin: 5px 0; }
        .info { color: #0073aa; background: #f0f6fc; padding: 10px 15px; border-radius: 4px; margin: 5px 0; }
        pre { background: #f6f7f7; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 13px; }
        .btn-danger { background: #d63638; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 20px; }
        .btn-danger:hover { background: #b32d2e; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f6f7f7; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Migração da Tabela de Pedidos</h1>
    <p><strong>Tabela:</strong> ' . esc_html($table_name) . '</p>
    <hr>';

// Adicionar colunas faltantes
$added_count = 0;
$skipped_count = 0;

foreach ($required_columns as $column_name => $column_definition) {
    if (in_array($column_name, $existing_columns)) {
        $results[] = "<div class='warning'>✓ Coluna <strong>$column_name</strong> já existe - pulando</div>";
        $skipped_count++;
    } else {
        // Determinar posição (AFTER qual coluna)
        $after = '';
        switch ($column_name) {
            case 'subtotal':
                $after = 'customer_gender';
                break;
            case 'discount':
                $after = 'subtotal';
                break;
            case 'coupon_code':
                $after = 'discount';
                break;
            case 'coupon_id':
                $after = 'coupon_code';
                break;
            case 'pix_discount':
                $after = 'coupon_id';
                break;
            case 'student_discount':
                $after = 'pix_discount';
                break;
            case 'is_student':
                $after = 'student_discount';
                break;
        }
        
        $sql = "ALTER TABLE $table_name ADD COLUMN $column_name $column_definition";
        if ($after && in_array($after, $existing_columns)) {
            $sql .= " AFTER $after";
        }
        
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            $results[] = "<div class='success'>✅ Coluna <strong>$column_name</strong> adicionada com sucesso!</div>";
            $existing_columns[] = $column_name; // Atualizar lista para próximas iterações
            $added_count++;
        } else {
            $errors[] = "<div class='error'>❌ Erro ao adicionar coluna <strong>$column_name</strong>: " . esc_html($wpdb->last_error) . "</div>";
        }
    }
}

// Exibir resultados
echo '<h2>Resultados da Migração</h2>';

if (!empty($results)) {
    foreach ($results as $result) {
        echo $result;
    }
}

if (!empty($errors)) {
    foreach ($errors as $error) {
        echo $error;
    }
}

// Resumo
echo '<div class="info" style="margin-top: 20px;">
    <strong>Resumo:</strong><br>
    • Colunas adicionadas: ' . $added_count . '<br>
    • Colunas já existentes: ' . $skipped_count . '<br>
    • Erros: ' . count($errors) . '
</div>';

// Verificar estrutura final
echo '<h2>Estrutura Atual da Tabela</h2>';
$final_columns = $wpdb->get_results("DESCRIBE $table_name");

echo '<table>
    <thead>
        <tr>
            <th>Coluna</th>
            <th>Tipo</th>
            <th>Null</th>
            <th>Default</th>
        </tr>
    </thead>
    <tbody>';

foreach ($final_columns as $col) {
    $highlight = in_array($col->Field, array_keys($required_columns)) ? 'style="background: #edfaef;"' : '';
    echo "<tr $highlight>
        <td><strong>" . esc_html($col->Field) . "</strong></td>
        <td>" . esc_html($col->Type) . "</td>
        <td>" . esc_html($col->Null) . "</td>
        <td>" . esc_html($col->Default ?? 'NULL') . "</td>
    </tr>";
}

echo '</tbody></table>';

// Teste de contagem
$order_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo '<div class="info">
    <strong>Total de pedidos na tabela:</strong> ' . intval($order_count) . '
</div>';

// Aviso de segurança
echo '<hr>
<div class="error">
    <strong>⚠️ IMPORTANTE - SEGURANÇA:</strong><br>
    Após confirmar que a migração funcionou, <strong>DELETE ESTE ARQUIVO</strong> imediatamente!<br>
    <code>' . esc_html(__FILE__) . '</code>
</div>';

echo '</div>
</body>
</html>';

exit;
