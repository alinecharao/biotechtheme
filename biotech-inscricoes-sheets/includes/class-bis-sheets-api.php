<?php
if (!defined('ABSPATH')) exit;

class BIS_Sheets_API {
    private $auth;
    private $base = 'https://sheets.googleapis.com/v4/spreadsheets';

    public function __construct(BIS_Google_Auth $auth) { $this->auth = $auth; }

    public static function spreadsheet_id($input) {
        $input = trim((string) $input);
        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9_-]+)#', $input, $match)) return $match[1];
        return preg_match('/^[a-zA-Z0-9_-]{20,}$/', $input) ? $input : '';
    }

    public function metadata($spreadsheet_id) {
        return $this->request('GET', '/' . rawurlencode($spreadsheet_id) . '?fields=spreadsheetId,properties.title,sheets.properties');
    }

    public function create_tab($spreadsheet_id, $title) {
        $result = $this->request('POST', '/' . rawurlencode($spreadsheet_id) . ':batchUpdate', array('requests' => array(array(
            'addSheet' => array('properties' => array('title' => $title, 'gridProperties' => array('frozenRowCount' => 5))),
        ))));
        if (is_wp_error($result)) return $result;
        return isset($result['replies'][0]['addSheet']['properties']) ? $result['replies'][0]['addSheet']['properties'] : new WP_Error('bis_tab_create', 'O Google não retornou os dados da nova aba.');
    }

    public function rename_tab($spreadsheet_id, $sheet_id, $title) {
        return $this->request('POST', '/' . rawurlencode($spreadsheet_id) . ':batchUpdate', array('requests' => array(array(
            'updateSheetProperties' => array(
                'properties' => array('sheetId' => intval($sheet_id), 'title' => $title),
                'fields' => 'title',
            ),
        ))));
    }

    public function delete_tab($spreadsheet_id, $sheet_id) {
        return $this->request('POST', '/' . rawurlencode($spreadsheet_id) . ':batchUpdate', array('requests' => array(array(
            'deleteSheet' => array('sheetId' => intval($sheet_id)),
        ))));
    }

    /**
     * Ordena as inscrições pelo ID do pedido (coluna M, oculta).
     * Como os IDs são crescentes, isso mantém a mesma ordem cronológica
     * mesmo quando uma inscrição antiga é recuperada posteriormente.
     */
    public function sort_tab($spreadsheet_id, $sheet_id) {
        return $this->request('POST', '/' . rawurlencode($spreadsheet_id) . ':batchUpdate', array('requests' => array(array(
            'sortRange' => array(
                'range' => array(
                    'sheetId' => intval($sheet_id),
                    'startRowIndex' => 5,
                    'startColumnIndex' => 0,
                    'endColumnIndex' => 13,
                ),
                'sortSpecs' => array(
                    array(
                        'dimensionIndex' => 12,
                        'sortOrder' => 'ASCENDING',
                    ),
                ),
            ),
        ))));
    }

    public function setup_tab($spreadsheet_id, $sheet_id, $title, $course, $class_name, $date) {
        $range = $this->range($title, 'A1:M5');
        $rows = array(
            array('Curso', $course),
            array('Turma', $class_name),
            array('Data', $date),
            array(),
            array('Data do pedido', 'Nome', 'E-mail', 'CPF', 'Telefone', 'Gênero', 'Como nos encontrou', 'Tipo profissional', 'Comprovação / CRMV', 'Método de pagamento', 'Valor', 'Cupom', 'ID do pedido'),
        );
        $write = $this->request('PUT', '/' . rawurlencode($spreadsheet_id) . '/values/' . $range . '?valueInputOption=USER_ENTERED', array('values' => $rows));
        if (is_wp_error($write)) return $write;
        return $this->request('POST', '/' . rawurlencode($spreadsheet_id) . ':batchUpdate', array('requests' => array(
            array('repeatCell' => array('range' => array('sheetId' => intval($sheet_id), 'startRowIndex' => 0, 'endRowIndex' => 3, 'startColumnIndex' => 0, 'endColumnIndex' => 2), 'cell' => array('userEnteredFormat' => array('textFormat' => array('bold' => true))), 'fields' => 'userEnteredFormat.textFormat.bold')),
            array('repeatCell' => array('range' => array('sheetId' => intval($sheet_id), 'startRowIndex' => 4, 'endRowIndex' => 5, 'startColumnIndex' => 0, 'endColumnIndex' => 13), 'cell' => array('userEnteredFormat' => array('textFormat' => array('bold' => true, 'foregroundColor' => array('red' => 1, 'green' => 1, 'blue' => 1)), 'backgroundColor' => array('red' => 0.13, 'green' => 0.42, 'blue' => 0.24))), 'fields' => 'userEnteredFormat')),
            $this->body_format_request($sheet_id),
            $this->currency_format_request($sheet_id),
            array('updateDimensionProperties' => array('range' => array('sheetId' => intval($sheet_id), 'dimension' => 'COLUMNS', 'startIndex' => 12, 'endIndex' => 13), 'properties' => array('hiddenByUser' => true), 'fields' => 'hiddenByUser')),
            array('autoResizeDimensions' => array('dimensions' => array('sheetId' => intval($sheet_id), 'dimension' => 'COLUMNS', 'startIndex' => 0, 'endIndex' => 12))),
        )));
    }

    public function format_tab($spreadsheet_id, $sheet_id) {
        return $this->request('POST', '/' . rawurlencode($spreadsheet_id) . ':batchUpdate', array('requests' => array(
            $this->body_format_request($sheet_id),
            $this->currency_format_request($sheet_id),
        )));
    }

    public function order_index($spreadsheet_id, $title) {
        $result = $this->request('GET', '/' . rawurlencode($spreadsheet_id) . '/values/' . $this->range($title, 'M6:M'));
        if (is_wp_error($result)) return $result;
        $index = array();
        foreach ((array) (isset($result['values']) ? $result['values'] : array()) as $offset => $row) {
            if (isset($row[0]) && $row[0] !== '') $index[(string) $row[0]] = $offset + 6;
        }
        return $index;
    }

    public function write_order($spreadsheet_id, $title, $row_number, $values) {
        if ($row_number) {
            return $this->request('PUT', '/' . rawurlencode($spreadsheet_id) . '/values/' . $this->range($title, 'A' . intval($row_number) . ':M' . intval($row_number)) . '?valueInputOption=USER_ENTERED', array('values' => array($values)));
        }
        return $this->request('POST', '/' . rawurlencode($spreadsheet_id) . '/values/' . $this->range($title, 'A:M') . ':append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS', array('values' => array($values)));
    }

    public function delete_row($spreadsheet_id, $sheet_id, $row_number) {
        return $this->request('POST', '/' . rawurlencode($spreadsheet_id) . ':batchUpdate', array('requests' => array(array('deleteDimension' => array('range' => array(
            'sheetId' => intval($sheet_id), 'dimension' => 'ROWS', 'startIndex' => intval($row_number) - 1, 'endIndex' => intval($row_number),
        ))))));
    }

    private function range($title, $cells) {
        return "'" . str_replace("'", "''", $title) . "'!" . $cells;
    }

    private function body_format_request($sheet_id) {
        return array('repeatCell' => array(
            'range' => array('sheetId' => intval($sheet_id), 'startRowIndex' => 5, 'startColumnIndex' => 0, 'endColumnIndex' => 13),
            'cell' => array('userEnteredFormat' => array(
                'backgroundColor' => array('red' => 1, 'green' => 1, 'blue' => 1),
                'textFormat' => array('bold' => false, 'foregroundColor' => array('red' => 0, 'green' => 0, 'blue' => 0)),
            )),
            'fields' => 'userEnteredFormat.backgroundColor,userEnteredFormat.textFormat.bold,userEnteredFormat.textFormat.foregroundColor',
        ));
    }

    private function currency_format_request($sheet_id) {
        return array('repeatCell' => array(
            'range' => array('sheetId' => intval($sheet_id), 'startRowIndex' => 5, 'startColumnIndex' => 10, 'endColumnIndex' => 11),
            'cell' => array('userEnteredFormat' => array('numberFormat' => array('type' => 'CURRENCY', 'pattern' => '"R$"#,##0.00'))),
            'fields' => 'userEnteredFormat.numberFormat',
        ));
    }

    private function request($method, $path, $body = null) {
        $token = $this->auth->access_token();
        if (is_wp_error($token)) return $token;
        $args = array('method' => $method, 'timeout' => 30, 'headers' => array('Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'));
        if ($body !== null) $args['body'] = wp_json_encode($body);
        $response = wp_remote_request($this->base . $path, $args);
        if (is_wp_error($response)) return $response;
        $code = wp_remote_retrieve_response_code($response);
        $decoded = json_decode(wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300) {
            $message = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'Falha ao acessar o Google Sheets.';
            return new WP_Error('bis_google_' . $code, $message, array('status' => $code));
        }
        return is_array($decoded) ? $decoded : array();
    }
}