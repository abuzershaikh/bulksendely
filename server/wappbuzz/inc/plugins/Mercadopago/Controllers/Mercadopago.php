<?php

namespace Plugins\Mercadopago\Controllers;

class Mercadopago extends \CodeIgniter\Controller
{
    public function __construct()
    {
        $reflect = new \ReflectionClass(get_called_class());
        $this->module = strtolower($reflect->getShortName());
        $this->config = include realpath(__DIR__ . "/../Config.php");

        $this->access_token = get_option("mercadopago_access_token");

        if (!get_option("mercadopago_one_time_status", 0) || $this->access_token == "") {
            redirect_to(base_url());
        }

    }

    public function index($ids = "")
    {

try {
    
    $parsed_body = json_decode(file_get_contents('php://input'), true);
    $TIPO_PAGAMENTO = $parsed_body["payment_method_id"];
    $parsed_body["notification_url"] = base_url() . "/mercadopago/success";
    $parsed_body["capture"] = true;
    
} catch(Exception $exception) {

    $response_fields = array('error_message' => $exception->getMessage());
    echo json_encode($response_fields);
    die;

}

// ENVIAR
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.mercadopago.com/v1/payments',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($parsed_body),
    CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $this->access_token,
        'Content-Type: application/json',
    ),
));

$response = curl_exec($curl);
curl_close($curl);

$payment = json_decode($response);

if($payment->id === null) {
    $error_message = 'Erro ao realizar o pagamento, contacte com o suporte.';
    if($payment->message !== null) {
        $sdk_error_message = $payment->message;
        $error_message = $sdk_error_message !== null ? $sdk_error_message : $error_message;
    }
    if($error_message == "Invalid transaction_amount"){
        $error_message = "Valor de pagamento inválido";
    }
    echo json_encode(array("status" => false, "message" => $error_message));
    die;
    //throw new Exception($error_message);
} 

    $order_id = $payment->external_reference;
    $status = $payment->status;
    $payment_method_id = $payment->payment_method_id;
    $transaction_amount = $payment->transaction_amount;
    $id_mercadopago = $payment->id;           

    $random_bytes = random_bytes(6);
    $subscription_id = bin2hex($random_bytes);
    $this->plan = get_payment_plan(uri("segment", 3), uri("segment", 4));
    $db = \Config\Database::connect(); 
    $db->table(TB_PAYMENT_SUBSCRIPTIONS)->insert([
        'ids' => $ids,
        'uid' => get_user("id"),
        'plan' => $this->plan->id,
        'by' => $this->plan->by,                
        'type' => 'mercadopago',
        'subscription_id' => $subscription_id,
        'customer_id' => $order_id,
        'created' => time(),
    ]);    

$status_pag = array(
    "approved" => "Aprovado",
    "rejected" => "Rejeitado",
    "in_process" => "Pendente aprovação",
);

$status_pag_motivo = array(
"approved" => array("accredited" => "Pronto, seu pagamento foi aprovado!"),
"pending" => array(
        "pending_waiting_transfer" => "Pagamento pendente (esperando transferência)!",
        "pending_waiting_payment" => "Pagamento pendente (esperando pagamento)!"
),
"in_process" => array(
    "pending_contingency" => "Estamos processando o pagamento. Não se preocupe, em menos de 2 dias úteis informaremos por e-mail se foi creditado.",
    "pending_review_manual" => "Estamos processando seu pagamento. Não se preocupe, em menos de 2 dias úteis informaremos por e-mail se foi creditado ou se necessitamos de mais informação."
),
"rejected" => array(
    "cc_rejected_bad_filled_card_number" => "Revise o número do cartão.",
    "cc_rejected_bad_filled_date" => "Revise a data de vencimento.",
    "cc_rejected_bad_filled_other" => "Revise os dados.",
    "cc_rejected_bad_filled_security_code" => "Revise o código de segurança do cartão.",
    "cc_rejected_blacklist" => "Não pudemos processar seu pagamento.",
    "cc_rejected_call_for_authorize" => "Você deve autorizar ao payment_method_id o pagamento do valor ao Mercado Pago.",
    "cc_rejected_card_disabled" => "Ligue para o payment_method_id para ativar seu cartão. O telefone está no verso do seu cartão.",
    "cc_rejected_card_error" => "Não conseguimos processar seu pagamento.",
    "cc_rejected_duplicated_payment" => "Você já efetuou um pagamento com esse valor. Caso precise pagar novamente, utilize outro cartão ou outra forma de pagamento.",
    "cc_rejected_high_risk" => "Seu pagamento foi recusado. Escolha outra forma de pagamento. Recomendamos meios de pagamento em dinheiro.",
    "cc_rejected_insufficient_amount" => "O payment_method_id possui saldo insuficiente.",
    "cc_rejected_invalid_installments" => "O payment_method_id não processa pagamentos em installments parcelas.",
    "cc_rejected_max_attempts" => "Você atingiu o limite de tentativas permitido. Escolha outro cartão ou outra forma de pagamento.",
    "cc_rejected_other_reason" => "payment_method_id não processa o pagamento.",
    "cc_rejected_card_type_not_allowed" => "O pagamento foi rejeitado porque o usuário não tem a função crédito habilitada em seu cartão multiplo (débito e crédito)."
)
);

$transaction_data = array(
    'id' => $payment->id,
    'status' => true,
    'tipo' => $TIPO_PAGAMENTO,
    'message' => $status_pag_motivo[$payment->status][$payment->status_detail],
);

echo json_encode($transaction_data);
die;

        
}

    public function success()
    {
    
    header("Access-Control-Allow-Origin: *");
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data["data"]["id"];

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/'. $id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
    'Authorization: Bearer '. $this->access_token,
   ),
  ));

    $response = curl_exec($curl);
    $resultado = json_decode($response);
    curl_close($curl);

    $paymentStatus = $resultado->status;
    $external_reference = $resultado->external_reference;
    $transactionAmount = $resultado->transaction_amount;
    $payment_method_id = $resultado->payment_method_id;
    $payment_type_id = $resultado->payment_type_id;

    $user = db_get("*", TB_PAYMENT_SUBSCRIPTIONS, ["customer_id" => $external_reference]);
    
    $plan = $user->plan;
    $by = $user->by;
    $type = $user->type;
    $subscription_id = $user->subscription_id;
    $customer_id = $user->customer_id;
    $uid = $user->uid;

    $existing_payment = db_get("*", TB_PAYMENT_HISTORY, ["transaction_id" => $customer_id]);

    if (empty($existing_payment)) {
    $data = [
        'uid' => $uid,
        'plan' => $plan,                
        'type' => $type,
        'transaction_id' => $customer_id,
        'amount' => $transactionAmount,
        'by' => $by,
    ];

    if ($paymentStatus === 'approved' && ($payment_method_id === 'bolbradesco' || $payment_method_id === 'pix' || $payment_type_id === 'credit_card')) {
        $save_result = payment_save($data);

        if ($save_result === true) {
            echo json_encode(array("status" => "pago"));
        } else {
            echo json_encode(array("message" => "Erro ao salvar pagamento"));
        }
    } else {
        echo json_encode(array("status" => "Pending"));
    }
    } else {
    echo json_encode(array("message" => "Já existe um pagamento com esse id", "status" => "pago"));
  }
die;


 }
  
}