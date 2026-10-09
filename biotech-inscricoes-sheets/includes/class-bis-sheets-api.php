<?php
if (!defined('ABSPATH')) exit;

class BIS_Sheets_API {
    private $auth;
    private $base = 'https://sheets.googleapis.com/v4/spreadsheets';
    private $metadata_cache = array();

    public function __construct(BIS_Google_Auth $auth) {
        $this->auth = $auth;
    }

    public static function spreadsheet_id($input) {
        $input = trim((string) $input);
        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9_-]+)#', $input, $match)) return $match[1];
        return preg_match('/^[a-zA-Z0-9_-]{20,}$/', $input) ? $input : '';
    }

    public function metadata($spreadsheet_id, $refresh = false) {
        $key = (string) $spreadsheet_id;
        if (!$refresh && isset($this->metadata_cache[$key])) return $this->metadata_cache[$key];

        $result = $this->request(
            'GET',
            '/' . rawurlencode($spreadsheet_id) . '?fields=spreadsheetId,properties.title,sheets.properties'
        );
        if (!is_wp_error($result)) $this->metadata_cache[$key] = $result;
        return $result;
    }

    public function create_tab($spreadsheet_id, $title) {
        $result = $this->request(
            'POST',
            '/' . rawurlencode($spreadsheet_id) . ':batchUpdate',
            array(
                'requests' => array(
                    array(
                        'addSheet' => array(
                            'properties' => array(
                                'title' => $title,
                                'gridProperties' => array(
                                    'frozenRowCount' => 5,
                                    'rowCount' => 2000,
                                    'columnCount' => 14,
                                ),
                            ),
                        ),
                    ),
                ),
            )
        );
        if (is_wp_error($result)) return $result;
        unset($this->metadata_cache[(string) $spreadsheet_id]);
        return $result['replies'][0]['addSheet']['properties'] ?? new WP_Error('bis_tab_create', 'O Google não retornou a nova guia.');
    }

    public function rename_tab($spreadsheet_id, $sheet_id, $title) {
        $result = $this->request(
            'POST',
            '/' . rawurlencode($spreadsheet_id) . ':batchUpdate',
            array(
                'requests' => array(
                    array(
                        'updateSheetProperties' => array(
                            'properties' => array(
                                'sheetId' => intval($sheet_id),
                                'title' => $title,
                            ),
                            'fields' => 'title',
                        ),
                    ),
                ),
            )
        );
        if (!is_wp_error($result)) unset($this->metadata_cache[(string) $spreadsheet_id]);
        return $result;
    }

    public function delete_tab($spreadsheet_id, $sheet_id) {
        $result = $this->request(
            'POST',
            '/' . rawurlencode($spreadsheet_id) . ':batchUpdate',
            array('requests' => array(array('deleteSheet' => array('sheetId' => intval($sheet_id)))))
        );
        if (!is_wp_error($result)) unset($this->metadata_cache[(string) $spreadsheet_id]);
        return $result;
    }

    public function read_values($spreadsheet_id, $title, $cells = 'A1:N') {
        return $this->request(
            'GET',
            '/' . rawurlencode($spreadsheet_id) . '/values/' . rawurlencode($this->range($title, $cells))
        );
    }

    public function write_tab($spreadsheet_id, $sheet_id, $title, $course_name, $class_name, $date_text, $rows, $format = false) {
        $existing = $this->read_values($spreadsheet_id, $title, 'A1:N');
        if (is_wp_error($existing)) return $existing;

        $headers = array(
            'Data da inscrição',
            'Nome',
            'E-mail',
            'CPF',
            'Telefone',
            'Gênero',
            'Como nos encontrou',
            'Tipo de inscrição',
            'Comprovante / CRMV',
            'Método de pagamento',
            'Valor',
            'Cupom',
            'Status',
            'ID do pedido',
        );

        $values = array(
            array('Nome do curso', $course_name),
            array('Turma', $class_name),
            array('Data', $date_text),
            array(),
            $headers,
        );

        foreach ($rows as $row) $values[] = array_values($row);

        $old_count = !empty($existing['values']) ? count($existing['values']) : 0;
        $target_count = max(count($values), $old_count, 5);
        while (count($values) < $target_count) $values[] = array_fill(0, 14, '');

        $range = $this->range($title, 'A1:N' . $target_count);
        $write = $this->request(
            'PUT',
            '/' . rawurlencode($spreadsheet_id) . '/values/' . rawurlencode($range) . '?valueInputOption=USER_ENTERED',
            array('values' => $values)
        );
        if (is_wp_error($write)) return $write;

        if ($format) {
            $formatted = $this->format_tab($spreadsheet_id, $sheet_id);
            if (is_wp_error($formatted)) return $formatted;
        }

        return true;
    }

    public function format_tab($spreadsheet_id, $sheet_id) {
        $sheet_id = intval($sheet_id);
        $requests = array(
            array(
                'repeatCell' => array(
                    'range' => array(
                        'sheetId' => $sheet_id,
                        'startRowIndex' => 0,
                        'endRowIndex' => 3,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 2,
                    ),
                    'cell' => array(
                        'userEnteredFormat' => array(
                            'backgroundColor' => array('red' => 1, 'green' => 1, 'blue' => 1),
                            'textFormat' => array(
                                'bold' => false,
                                'foregroundColor' => array('red' => 0, 'green' => 0, 'blue' => 0),
                            ),
                        ),
                    ),
                    'fields' => 'userEnteredFormat',
                ),
            ),
            array(
                'repeatCell' => array(
                    'range' => array(
                        'sheetId' => $sheet_id,
                        'startRowIndex' => 0,
                        'endRowIndex' => 3,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 1,
                    ),
                    'cell' => array(
                        'userEnteredFormat' => array(
                            'textFormat' => array('bold' => true),
                        ),
                    ),
                    'fields' => 'userEnteredFormat.textFormat.bold',
                ),
            ),
            array(
                'repeatCell' => array(
                    'range' => array(
                        'sheetId' => $sheet_id,
                        'startRowIndex' => 4,
                        'endRowIndex' => 5,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 14,
                    ),
                    'cell' => array(
                        'userEnteredFormat' => array(
                            'backgroundColor' => array('red' => 0.13, 'green' => 0.42, 'blue' => 0.24),
                            'textFormat' => array(
                                'bold' => true,
                                'foregroundColor' => array('red' => 1, 'green' => 1, 'blue' => 1),
                            ),
                        ),
                    ),
                    'fields' => 'userEnteredFormat',
                ),
            ),
            array(
                'repeatCell' => array(
                    'range' => array(
                        'sheetId' => $sheet_id,
                        'startRowIndex' => 5,
                        'endRowIndex' => 2000,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 14,
                    ),
                    'cell' => array(
                        'userEnteredFormat' => array(
                            'backgroundColor' => array('red' => 1, 'green' => 1, 'blue' => 1),
                            'textFormat' => array(
                                'bold' => false,
                                'foregroundColor' => array('red' => 0, 'green' => 0, 'blue' => 0),
                            ),
                        ),
                    ),
                    'fields' => 'userEnteredFormat.backgroundColor,userEnteredFormat.textFormat.bold,userEnteredFormat.textFormat.foregroundColor',
                ),
            ),
            array(
                'repeatCell' => array(
                    'range' => array(
                        'sheetId' => $sheet_id,
                        'startRowIndex' => 5,
                        'endRowIndex' => 2000,
                        'startColumnIndex' => 10,
                        'endColumnIndex' => 11,
                    ),
                    'cell' => array(
                        'userEnteredFormat' => array(
                            'numberFormat' => array(
                                'type' => 'CURRENCY',
                                'pattern' => '"R$"#,##0.00',
                            ),
                        ),
                    ),
                    'fields' => 'userEnteredFormat.numberFormat',
                ),
            ),
            array(
                'updateDimensionProperties' => array(
                    'range' => array(
                        'sheetId' => $sheet_id,
                        'dimension' => 'COLUMNS',
                        'startIndex' => 13,
                        'endIndex' => 14,
                    ),
                    'properties' => array('hiddenByUser' => true),
                    'fields' => 'hiddenByUser',
                ),
            ),
            array(
                'autoResizeDimensions' => array(
                    'dimensions' => array(
                        'sheetId' => $sheet_id,
                        'dimension' => 'COLUMNS',
                        'startIndex' => 0,
                        'endIndex' => 13,
                    ),
                ),
            ),
        );

        return $this->request(
            'POST',
            '/' . rawurlencode($spreadsheet_id) . ':batchUpdate',
            array('requests' => $requests)
        );
    }

    private function range($title, $cells) {
        return "'" . str_replace("'", "''", $title) . "'!" . $cells;
    }

    private function request($method, $path, $body = null) {
        $token = $this->auth->access_token();
        if (is_wp_error($token)) return $token;

        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ),
        );
        if ($body !== null) $args['body'] = wp_json_encode($body);

        $response = wp_remote_request($this->base . $path, $args);
        if (is_wp_error($response)) return $response;

        $code = wp_remote_retrieve_response_code($response);
        $decoded = json_decode(wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300) {
            $message = $decoded['error']['message'] ?? 'Falha ao acessar o Google Sheets.';
            $quota = $code === 429 || ($code === 403 && stripos($message, 'quota') !== false);
            return new WP_Error($quota ? 'bis_rate_limited' : 'bis_google_' . $code, $message, array('status' => $code));
        }

        return is_array($decoded) ? $decoded : array();
    }
}
