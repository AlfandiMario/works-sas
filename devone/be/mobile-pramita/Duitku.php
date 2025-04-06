<?php
class Duitku extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    function rdr()
    {
        $merchantOrderId = isset($_GET['merchantOrderId']) ? $_GET['merchantOrderId'] : null;
        $reference = isset($_GET['reference']) ? $_GET['reference'] : null;
        $resultCode = isset($_GET['resultCode']) ? $_GET['resultCode'] : null;
        $sql = "select count(*) total from duitku_cb where duitkuCbReply = 'Redirect' and duitkuCbReference='$reference'";
        $qry = $this->db->query($sql);
        if (!$qry) {
            $date = Date("Y-m-d H:i:s");
            file_put_contents(
                "/xtmp/log-duitku-cb.log",
                "$date Err Query : " . $this->db->error()["message"] . "\n",
                FILE_APPEND
            );
            return;
        }
        $rows = $qry->result_array();
        if ($rows[0]["total"] == 0) {
            $sql = "insert into duitku_cb(duitkuCbReply,duitkuCbPaymentStatus,duitkuCbReference,duitkuCbMerchantOrderId)
            values('Redirect',?,?,?)";
            $qry = $this->db->query($sql, array($resultCode, $reference, $merchantOrderId));
        } else {
            $sql = "update duitku_cb
            set duitkuCbPaymentStatus = ? where 
                duitkuCbReply='Redirect' 
                and duitkuCbReference= ? 
                and duitkuCbMerchantOrderId = ? ";
            $qry = $this->db->query($sql, array($resultCode, $reference, $merchantOrderId));
        }
        if (!$qry) {
            $date = Date("Y-m-d H:i:s");
            file_put_contents(
                "/xtmp/log-duitku-cb.log",
                "$date Err Query : " . $this->db->error()["message"] . "\n",
                FILE_APPEND
            );
        }
        header("Location: https://devregonline.pramita.co.id/#/listorder");
    }
    function get_api_key($merchantOrderId)
    {
        $sql = "select 
        M_BranchPgApiKey
        from 
        t_transaction
        join m_branch_pg on T_TransactionM_BranchID = M_BranchPgM_BranchID
        where T_TransactionNumbering = ?";
        $qry = $this->db->query($sql, array($merchantOrderId));
        if (!$qry) {
            $date = Date("Y-m-d H:i:s");
            file_put_contents(
                "/xtmp/log-duitku-cb.log",
                "$date Err Get Api Key $merchantOrderId : " . $this->db->error()["message"] . "\n",
                FILE_APPEND
            );
            return 'd2fc54e0d14fb99cdefd0ad400ccdd82';
        }
        $rows = $qry->result_array();
        if (count($rows) == 0) {
            $date = Date("Y-m-d H:i:s");
            file_put_contents(
                "/xtmp/log-duitku-cb.log",
                "$date Err Get Api Key $merchantOrderId : Not Found " . "\n",
                FILE_APPEND
            );
            return 'd2fc54e0d14fb99cdefd0ad400ccdd82';
        }
        $apiKey =  $rows[0]["M_BranchPgApiKey"];
        if ($apiKey == "") {
            $date = Date("Y-m-d H:i:s");
            file_put_contents(
                "/xtmp/log-duitku-cb.log",
                "$date Err Get Api Key $merchantOrderId : |$apiKey|  " . "\n",
                FILE_APPEND
            );
            return 'd2fc54e0d14fb99cdefd0ad400ccdd82';
        }
        return $apiKey;
    }
    function index()
    {

        $merchantCode = isset($_POST['merchantCode']) ? $_POST['merchantCode'] : null;
        $amount = isset($_POST['amount']) ? $_POST['amount'] : null;
        $merchantOrderId = isset($_POST['merchantOrderId']) ? $_POST['merchantOrderId'] : null;
        $productDetail = isset($_POST['productDetail']) ? $_POST['productDetail'] : null;
        $additionalParam = isset($_POST['additionalParam']) ? $_POST['additionalParam'] : null;
        $paymentMethod = isset($_POST['paymentCode']) ? $_POST['paymentCode'] : null;
        $resultCode = isset($_POST['resultCode']) ? $_POST['resultCode'] : null;
        $merchantUserId = isset($_POST['merchantUserId']) ? $_POST['merchantUserId'] : null;
        $reference = isset($_POST['reference']) ? $_POST['reference'] : null;
        $signature = isset($_POST['signature']) ? $_POST['signature'] : null;
        $apiKey = $this->get_api_key($merchantOrderId);

        if (!empty($merchantCode) && !empty($amount) && !empty($merchantOrderId) && !empty($signature)) {
            $params = $merchantCode . $amount . $merchantOrderId . $apiKey;
            $calcSignature = md5($params);
            if ($signature == $calcSignature) {
                $sql = "insert into duitku_cb(duitkuCbMerchantCode, duitkuCbAmount, duitkuCbMerchantOrderId,
                duitkuCbProductDetail, duitkuCbAdditionalParam, duitkuCbPaymentCode, 
                duitkuCbPaymentResultCode, duitkuCbMerchantUserID, duitkuCbReference,
                duitkuCbSignature, duitkuCbReply )
                values(?,?,?  ,?,?,?   ,?,?,?   ,?,?)";
                $qry = $this->db->query(
                    $sql,
                    array(
                        $merchantCode, $amount, $merchantOrderId,
                        $productDetail, $additionalParam, $paymentMethod,
                        $resultCode, $merchantUserId, $reference,
                        $signature, "SUCCESS"
                    )
                );
                if (!$qry) {
                    $date = Date("Y-m-d H:i:s");
                    file_put_contents(
                        "/xtmp/log-duitku-cb.log",
                        "$date Err Query : " . $this->db->error()["message"] . "\n",
                        FILE_APPEND
                    );
                    exit;
                }
                //update transaction lunas 
                if ($resultCode == "00") {
                    $sql = "update t_transaction set T_TransactionIsLunas = 'Y' where T_TransactionNumbering= ?";
                    $qry = $this->db->query($sql, array($merchantOrderId));
                    if (!$qry) {
                        $date = Date("Y-m-d H:i:s");
                        file_put_contents(
                            "/xtmp/log-duitku-cb.log",
                            "$date Err Update Lunas : " . $this->db->error()["message"] . "\n",
                            FILE_APPEND
                        );
                        exit;
                    }
                }
                echo "SUCCESS"; // Please response with success
            } else {
                $sql = "insert into duitku_cb(duitkuCbMerchantCode, duitkuCbAmount, duitkuCbMerchantOrderId,
                duitkuCbProductDetail, duitkuCbAdditionalParam, duitkuCbPaymentCode, 
                duitkuCbPaymentResultCode, duitkuCbMerchantUserID, duitkuCbReference,
                duitkuCbSignature, duitkuCbReply , duitkuCbIsProcessed)
                values(?,?,?  ,?,?,?   ,?,?,?   ,?,?,'X')";
                $qry = $this->db->query(
                    $sql,
                    array(
                        $merchantCode, $amount, $merchantOrderId,
                        $productDetail, $additionalParam, $paymentMethod,
                        $resultCode, $merchantUserId, $reference,
                        $signature, "Bad Signature $signature : $calcSignature"
                    )
                );
                if (!$qry) {
                    $date = Date("Y-m-d H:i:s");
                    file_put_contents(
                        "/xtmp/log-duitku-cb.log",
                        "$date Err Query : " . $this->db->error()["message"] . "\n",
                        FILE_APPEND
                    );
                    exit;
                }
                $date = Date("Y-m-d H:i:s");
                file_put_contents(
                    "/xtmp/log-duitku-cb.log",
                    "$date Bad Signature $signature : $calcSignature \n",
                    FILE_APPEND
                );
            }
        } else {
            $date = Date("Y-m-d H:i:s");
            file_put_contents("/xtmp/log-duitku-cb.log", "$date Bad Parameter", FILE_APPEND);
        }
    }
}
