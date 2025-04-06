<?php
class Gateway extends MY_Controller
{
    var $db_onedev;
    var $load;
    var $hostname;

    private $translationAPIUrl = 'https://devone.aplikasi.web.id/one-api/ai-lab-translate/ai_lab/sendToAi';

    public function index()
    {
        // ini di devcpone
        echo "API BE untuk Gateway AI Lab";
    }

    public function __construct()
    {
        parent::__construct();
        $this->db_onedev = $this->load->database('onedev', TRUE);
        $this->hostname = "devcpone.aplikasi.web.id";
    }

    public function translateLabArr($T_OrderHeaderID)
    {
        $sql = "SELECT 
        T_OrderDetailID as orderDetailID,
        T_OrderDetailT_TestID as T_TestID,
        T_OrderDetailResult as result,
        T_OrderDetailT_OrderHeaderID as T_OrderHeaderID
        FROM t_orderdetail
        WHERE  T_OrderDetailT_OrderHeaderID = ?
            AND T_OrderDetailIsActive = 'Y' 
            AND T_OrderDetailValidation    = 'Y'
            AND T_OrderDetailResult IS NOT NULL
        ";

        $query = $this->db_onedev->query($sql, array(
            $T_OrderHeaderID
        ));
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error($message);
            exit;
        }

        $array = $query->result_array();

        $notTranslated = [];
        $translated = [];

        $allData = [];

        /* Looping didalam array untuk mengambil order result */
        $inputs = [];
        foreach ($array as $key => $item) {
            /* Kalau pakai isNumeric nanti "0 - 1" tidak terfilter */
            if (!preg_match('/[a-zA-Z]/', $item['result'])) {

                $notTranslated[] = [
                    'orderDetailID' => $item['orderDetailID'],
                    'original' => $item['result'],
                    'translated' => $item['result'],
                    'confidence' => 1
                ];
                continue;
            }
            $translated[] = [
                'orderDetailID' => $item['orderDetailID'],
                'original' => $item['result'],
                'translated' => '',
                'confidence' => 0
            ];

            $inputs[] = $item['result'];
        }

        /* Jika panjang input lebih dari 0 maka lakukan curl */
        if (count($inputs) > 0) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://devone.aplikasi.web.id/one-api/ai-lab-translate/ai_lab/sendToAi',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query(['data' => $inputs]),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $result = json_decode($response, true);

            $outputs = $result['data']['output'];

            foreach ($outputs as $key => $output) {
                $translated[$key]['translated'] = $output['translated'];
                $translated[$key]['confidence'] = $output['confidence'];
            }
        }

        $sqlInsert = "INSERT INTO t_orderdetaillang_ai (
            T_OrderDetailLangAiT_OrderHeaderID,
            T_OrderDetailLangAiT_OrderDetailID,
            T_OrderDetailLangAiNat_LangID,
            T_OrderDetailLangAiResult,
            T_OrderDetailLangAiConfidence
        ) VALUES (?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        T_OrderDetailLangAiResult = VALUES(T_OrderDetailLangAiResult),
        T_OrderDetailLangAiConfidence = VALUES(T_OrderDetailLangAiConfidence),
        T_OrderDetailLangAiLastUpdated = NOW()";

        $this->db_onedev->trans_start(true); // (true) jika debugging

        foreach (array_merge($translated, $notTranslated) as $entry) {
            $query = $this->db_onedev->query($sqlInsert, [
                $T_OrderHeaderID,
                $entry['orderDetailID'],
                2,
                $entry['translated'],
                $entry['confidence']
            ]);

            if (!$query) {
                $message = $this->db_onedev->error();
                $message['qry'] = $this->db_onedev->last_query();
                $this->db_onedev->trans_rollback();
                $this->sys_error($message);
                exit;
            }
        }

        $this->db_onedev->trans_complete();
        $this->sys_ok("OK");
    }

    public function translateLabStr($T_OrderHeaderID)
    {
        $sql = "SELECT 
        T_OrderDetailID as orderDetailID,
        T_OrderDetailT_TestID as T_TestID,
        T_OrderDetailResult as result,
        T_OrderDetailT_OrderHeaderID as T_OrderHeaderID
        FROM t_orderdetail
        WHERE  T_OrderDetailT_OrderHeaderID = ?
            AND T_OrderDetailIsActive = 'Y' 
            AND T_OrderDetailValidation    = 'Y'
            AND T_OrderDetailResult IS NOT NULL ";

        $query = $this->db_onedev->query($sql, array(
            $T_OrderHeaderID
        ));
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error($message);
            exit;
        }

        $array = $query->result_array();

        $notTranslated = [];
        $translated = [];

        $inputs = [];
        foreach ($array as $key => $item) {
            /* Kalau pakai isNumeric nanti "0 - 1" tidak terfilter */
            if (!preg_match('/[a-zA-Z]/', $item['result'])) {
                $notTranslated[] = [
                    'orderDetailID' => $item['orderDetailID'],
                    'original' => $item['result'],
                    'translated' => $item['result'],
                    'confidence' => 1
                ];
                continue;
            }
            $translated[] = [
                'orderDetailID' => $item['orderDetailID'],
                'original' => $item['result'],
                'translated' => '',
                'confidence' => 0
            ];

            $inputs[] = $item['result'];
        }

        // Ambil input yang berbeda saja
        $distinctInputs = array_values(array_unique($inputs));
        $distinctInputMap = array_flip($distinctInputs); // Map untuk mengembalikan hasil ke translated

        $sql = "SELECT Translate_WordTo FROM translate_word
        WHERE Translate_WordNat_LangID = 2 
        AND Translate_WordIsActive = 'Y' 
        AND Translate_WordFrom = ?";

        $hasilLookup = [];
        $lookupMap = [];

        // Lookup ke tabel translate_word, jika sudah ada ambil dari sana
        foreach ($distinctInputs as $key => $item) {
            $query = $this->db_onedev->query($sql, $item);
            if (!$query) {
                $message = $this->db_onedev->error();
                $message['qry'] = $this->db_onedev->last_query();
                $this->sys_error($message);
                exit;
            }
            $result = $query->row_array();
            if ($result) {
                $hasilLookup[] = [
                    'original' => $item,
                    'translated' => $result['Translate_WordTo'],
                ];
                $lookupMap[] = $item;
                unset($distinctInputs[$key]);
                unset($distinctInputMap[$item]);
            }
        }

        // var_dump($distinctInputs, $distinctInputMap, $hasilLookup);

        $lookupMap = array_flip($lookupMap);
        // Memasukkan hasil lookup ke translated berdasar lookupMap
        foreach ($translated as &$entry) {
            $original = $entry['original'];
            if (isset($lookupMap[$original])) {
                $entry['translated'] = $hasilLookup[$lookupMap[$original]]['translated'];
                $entry['confidence'] = 1;
            }
        }
        // die(var_dump($hasilLookup, $distinctInputs, $distinctInputMap, $translated));

        $distictInputsStr = implode(" + ", $distinctInputs);

        if (count($distinctInputs) > 0) {
            $result = $this->calldevone($distictInputsStr);

            $outputAi = $result['data']['output'];
            $outputAi = trim($result['data']['output'], '"'); // remove double quote because apis handling
            $outputAiArray = explode(" + ", $outputAi); // separete by +
            $outputAiArray = array_map('trim', $outputAiArray); //remove space
            $outputAiConfidence = $result['data']['confidence'];

            $distinctInputMap = array_flip($distinctInputs);
            foreach ($translated as &$entry) {
                $original = $entry['original'];
                if (isset($distinctInputMap[$original])) {
                    $entry['translated'] = $outputAiArray[$distinctInputMap[$original]];
                    $entry['confidence'] = $outputAiConfidence;
                }
            }
        }
        // die(var_dump($outputAi, $translated));

        $sqlInsert = "INSERT INTO t_orderdetaillang_ai (
                T_OrderDetailLangAiT_OrderHeaderID,
                T_OrderDetailLangAiT_OrderDetailID,
                T_OrderDetailLangAiNat_LangID,
                T_OrderDetailLangAiResult,
                T_OrderDetailLangAiConfidence
            ) VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
            T_OrderDetailLangAiResult = VALUES(T_OrderDetailLangAiResult),
            T_OrderDetailLangAiConfidence = VALUES(T_OrderDetailLangAiConfidence),
            T_OrderDetailLangAiLastUpdated = NOW()";

        // $this->db_onedev->trans_start(true);
        $this->db_onedev->trans_start();

        foreach (array_merge($translated, $notTranslated) as $entry) {
            $query = $this->db_onedev->query($sqlInsert, [
                $T_OrderHeaderID,
                $entry['orderDetailID'],
                2,
                $entry['translated'],
                $entry['confidence']
            ]);
            if (!$query) {
                $message = $this->db_onedev->error();
                $message['qry'] = $this->db_onedev->last_query();
                $this->db_onedev->trans_rollback();
                $this->sys_error($message);
                exit;
            }
        }
        $this->db_onedev->trans_complete();
        $this->sys_ok([
            'translated' => $translated,
            'notTranslated' => $notTranslated
        ]);
        return true;
    }

    public function byorderheaderid()
    {
        $startDate = $this->sys_input['headerStartDate'];
        $endDate = $this->sys_input['headerEndDate'];

        $sql = "SELECT 
                T_OrderDetailT_OrderHeaderID as ID,
                T_OrderHeaderDate
                FROM t_orderdetail
                JOIN t_orderheader ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID
                    WHERE T_OrderDetailIsActive = 'Y' 
                    AND T_OrderDetailValidation    = 'Y'
                    AND T_OrderHeaderIsActive = 'Y'
                    AND T_OrderDetailResult IS NOT NULL
                    AND (DATE_FORMAT(T_OrderHeaderDate, '%Y-%m-%d') BETWEEN ? AND ?)
        group by T_OrderDetailT_OrderHeaderID 
        ";

        $query = $this->db_onedev->query($sql, [$startDate, $endDate]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            $this->sys_error($message);
            exit;
        }
        $result = $query->result_array();

        foreach ($result as $key => $item) {
            $this->translateLabStr($item['ID']);
        }
        $this->sys_ok("Berhasil inject translate ke t_orderdetaillang_ai");
    }

    public function calldevone($distictInputsStr)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://devone.aplikasi.web.id/one-api/ai-lab-translate/ai_lab/sendToAi',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($distictInputsStr), // JSON-encode the string with "data" key
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json', // Set content type to JSON
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response, true);
        return $result;
    }

    /* 
        *TRIAL* 
    */

    public function processOrderHeaders($startDate, $endDate, $usingAi = false)
    {
        try {
            if (!isset($startDate) || !isset($endDate)) {
                throw new Exception('Start date and end date are required');
            }

            $orderHeaders = $this->getOrderHeaders($startDate, $endDate);
            $successOrderHeaders = [];
            $failedOrderHeaders = [];

            foreach ($orderHeaders as $header) {
                try {
                    $this->db_onedev->trans_start();
                    // $this->db_onedev->trans_start(true);
                    $result = $this->translateLabResults($header['ID'], $usingAi);

                    if ($result['status'] === 'success') {
                        $this->db_onedev->trans_complete();
                        $successOrderHeaders[] = [
                            'orderHeaderId' => $header['ID'],
                            'date' => $header['T_OrderHeaderDate'],
                            'translationResults' => $result['data']
                        ];
                    } else {
                        throw new Exception($result['message']);
                    }
                } catch (Exception $e) {
                    $this->db_onedev->trans_rollback();
                    $failedOrderHeaders[] = [
                        'orderHeaderId' => $header['ID'],
                        'date' => $header['T_OrderHeaderDate'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            $resp = [
                'successOrderHeaders' => $successOrderHeaders,
                'failedOrderHeaders' => $failedOrderHeaders,
                'totalProcessed' => count($orderHeaders),
                'totalSuccess' => count($successOrderHeaders),
                'totalFailed' => count($failedOrderHeaders)
            ];
            $this->sys_ok($resp);
        } catch (Exception $e) {
            $this->sys_error($e->getMessage());
        }
    }

    private function getOrderHeaders($startDate, $endDate)
    {
        $sql = "SELECT 
                T_OrderDetailT_OrderHeaderID as ID,
                T_OrderHeaderDate
                FROM t_orderdetail
                JOIN t_orderheader ON T_OrderDetailT_OrderHeaderID = T_OrderHeaderID
                    WHERE T_OrderDetailIsActive = 'Y' 
                    AND T_OrderDetailValidation    = 'Y'
                    AND T_OrderHeaderIsActive = 'Y'
                    AND T_OrderDetailResult IS NOT NULL
                    AND (DATE_FORMAT(T_OrderHeaderDate, '%Y-%m-%d') BETWEEN ? AND ?)
        group by T_OrderDetailT_OrderHeaderID";

        $query = $this->db_onedev->query($sql, [$startDate, $endDate]);
        if (!$query) {
            $message = $this->db_onedev->error();
            $message['qry'] = $this->db_onedev->last_query();
            throw new Exception(json_encode($message));
            return;
        }
        return $query->result_array();
    }

    // translation process
    private function translateLabResults($orderHeaderId, $usingAI)
    {
        try {
            $results = $this->getLabResults($orderHeaderId);
            if (empty($results)) {
                throw new Exception('No lab results found');
            }

            list($translated, $notTranslated) = $this->processTranslations($results, $usingAI);
            // ? Kalau tidak ditranslate apa tetap insert?
            $this->saveTranslations($orderHeaderId, array_merge($translated, $notTranslated));

            return [
                'status' => 'success',
                'data' => [
                    'translated' => $translated,
                    'notTranslated' => $notTranslated
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    // get order detail from orderHeader 
    private function getLabResults($orderHeaderId)
    {
        return $this->db_onedev
            ->select('T_OrderDetailID as orderDetailID, T_OrderDetailT_TestID as T_TestID, 
                     T_OrderDetailResult as result, T_OrderDetailT_OrderHeaderID as T_OrderHeaderID')
            ->from('t_orderdetail')
            ->where('T_OrderDetailT_OrderHeaderID', $orderHeaderId)
            ->where('T_OrderDetailIsActive', 'Y')
            ->where('T_OrderDetailValidation', 'Y')
            ->where('T_OrderDetailResult IS NOT NULL')
            ->get()
            ->result_array();
    }

    private function processTranslations($results, $usingAI)
    {
        $translated = [];
        $notTranslated = [];
        $distinctInputs = [];

        foreach ($results as $result) {
            // Skip yang hanya berisi angka
            if (!preg_match('/[a-zA-Z]/', $result['result'])) {
                $notTranslated[] = $this->createTranslationEntry($result, $result['result'], 1);
                continue;
            }
            $distinctInputs[$result['result']] = true;
            $translated[] = $this->createTranslationEntry($result, '', 0);
        }

        $distinctInputs = array_keys($distinctInputs);

        // Get existing translations
        $existingTranslations = $this->getExistingTranslations($distinctInputs);

        if (!empty($existingTranslations)) {
            $translated = $this->applyExistingTranslations($translated, $existingTranslations);
            $distinctInputs = array_diff($distinctInputs, array_keys($existingTranslations));
        }

        if ($usingAI) {
            // Get new translations from API
            if (!empty($distinctInputs)) {
                $translated = $this->applyNewTranslations($translated, $distinctInputs);
            }
        }

        return [$translated, $notTranslated];
    }

    private function createTranslationEntry($result, $translatedText, $confidence)
    {
        return [
            'orderDetailID' => $result['orderDetailID'],
            'original' => $result['result'],
            'translated' => $translatedText,
            'confidence' => $confidence
        ];
    }

    private function getExistingTranslations($inputs)
    {
        $sql = "SELECT Translate_WordTo FROM translate_word
        WHERE Translate_WordNat_LangID = 2 
        AND Translate_WordIsActive = 'Y' 
        AND Translate_WordFrom = ?";

        $translations = [];
        foreach ($inputs as $input) {

            $query = $this->db_onedev->query($sql, $input);
            $result = $query->row_array();

            if ($result) {
                $translations[$input] = $result['Translate_WordTo'];
            }
        }
        return $translations;
    }

    private function applyExistingTranslations($translated, $translations)
    {
        foreach ($translated as &$entry) {
            if (isset($translations[$entry['original']])) {
                $entry['translated'] = $translations[$entry['original']];
                $entry['confidence'] = 1;
            }
        }
        return $translated;
    }

    private function applyNewTranslations($translated, $distinctInputs)
    {
        if (empty($distinctInputs)) {
            return $translated;
        }

        $inputString = implode(' + ', $distinctInputs);
        $apiResult = $this->callTranslationAPI($inputString);

        if ($apiResult['status'] === 'success') {
            $translations = array_combine($distinctInputs, $apiResult['translations']);

            foreach ($translated as &$entry) {
                if (isset($translations[$entry['original']]) && empty($entry['translated'])) {
                    $entry['translated'] = $translations[$entry['original']];
                    $entry['confidence'] = $apiResult['confidence'];
                }
            }
        }

        return $translated;
    }

    private function callTranslationAPI($inputString)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->translationAPIUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($inputString),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($error || $httpCode !== 200) {
            return [
                'status' => 'error',
                'message' => "API request failed: $error"
            ];
        }

        $result = json_decode($response, true);
        if (!isset($result['data']['output'])) {
            return [
                'status' => 'error',
                'message' => 'Invalid API response'
            ];
        }

        return [
            'status' => 'success',
            'translations' => explode(' + ', trim($result['data']['output'], '"')),
            'confidence' => $result['data']['confidence'] ?? 0.5
        ];
    }

    private function saveTranslations($orderHeaderId, $translations)
    {
        foreach ($translations as $translation) {
            $data = [
                'T_OrderDetailLangAiT_OrderHeaderID' => $orderHeaderId,
                'T_OrderDetailLangAiT_OrderDetailID' => $translation['orderDetailID'],
                'T_OrderDetailLangAiNat_LangID' => 2,
                'T_OrderDetailLangAiResult' => $translation['translated'],
                'T_OrderDetailLangAiConfidence' => $translation['confidence']
            ];

            $this->db_onedev->replace('t_orderdetaillang_ai', $data);

            if ($this->db_onedev->error()['code'] !== 0) {
                throw new Exception('Failed to save translation: ' . $this->db_onedev->error()['message']);
            }
        }
    }
}
