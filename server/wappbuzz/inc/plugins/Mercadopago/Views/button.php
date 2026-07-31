<?php
$id = isset($_GET['id']) ? $_GET['id'] : '';
$this->config = include realpath( __DIR__ . "/../Config.php");
$this->plan = get_payment_plan(uri("segment", 3), uri("segment", 4));
$title = $this->plan->name . " - " . ($this->plan->by == 2 ? __("Anualmente") : __("Mensalmente"));
$random_bytes = random_bytes(8);
$order_id = 'ORDS' . bin2hex($random_bytes); ?>
<?php if (get_user("id")): ?>
<?php if (get_option("mercadopago_one_time_status", 0)): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://unpkg.com/popper.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-beta/js/bootstrap.min.js"></script>
<script src="https://sdk.mercadopago.com/js/v2"></script>

<div class="col-md-6 mb-4">
    <div class="card card-shadow border b-r-10">
        <a id="contratarBtn" data-valor="<?php _e( $this->plan->amount )?>" data-descricao="<?php _e($title) ?>" class="card-body d-flex d-flex align-items-center bg-hover-light-primary" onclick="openModal()">
            <img src="<?php _e($logo)?>" class="w-60 h-60 border b-r-10 me-3">
            <div class="flex-grow-1">
                <div class="fw-6"><?php _e($title)?></div>
                <div class="text-gray-500 fs-12"><?php _e($desc)?></div>
            </div>
        </a>
    </div>
</div>
<div class="modal fade" id="basicModal" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
    <div class="modal-dialog custom-modal">
        <div style="border-radius: 18px; border: 0px;" class="modal-content">
            <div id="paymentBrick_container"></div>
            <div id="statusScreenBrick_container"></div>
            <div class="form-signin" id="form-pago" style="display:none;text-align: center; background:#171e2e; border-radius: 10px;">
                <h1 style="color:#fff; padding-top: 5%; padding-bottom: 5%" class="h3">Pagamento Aprovado!</h1>
                <img class="mb-4" src="<?php _e($img) ?>" alt="" width="120" height="120">
                <br>
                <h3 style="color:#fff;">Obrigado!</h3>
                <br>
                <div style="color:#fff; padding-bottom: 5%">Código do pagamento: <?php _e($id) ?></div>
                <br>
            </div>
        </div>
    </div>
</div>
<style>
    body{font-family:arial}
</style>
<script>
    var payment_check;
    const mp = new MercadoPago('<?php _e(get_option("mercadopago_public_token", "")) ?>', {
        locale: 'pt-BR'
    });
    const bricksBuilder = mp.bricks();
    const renderPaymentBrick = async (bricksBuilder, amount, description) => {
        const settings = {
            initialization: {
                amount: amount,
                payer: {
                    firstName: "",
                    lastName: "",
                    email: "",
                    identification: {
                        type: '',
                        number: '',
                    },
                    address: {
                        zipCode: '',
                        federalUnit: '',
                        city: '',
                        neighborhood: '',
                        streetName: '',
                        streetNumber: '',
                        complement: '',
                    }
                },
            },
            customization: {
                visual: {
                    style: {
                        theme: "dark",
                    },
                },
                paymentMethods: {
                    creditCard: "<?php _e( get_option("creditCard_status", 0)==1?"all":"" )?>",
                    debitCard: "<?php _e( get_option("debitCard_status", 0)==1?"all":"" )?>",
                    ticket: "<?php _e( get_option("ticket_status", 0)==1?"all":"" )?>",
                    bankTransfer: "<?php _e( get_option("bankTransfer_status", 0)==1?"all":"" )?>",
                    maxInstallments: "<?php _e( get_option("creditCard_maxInstallments", 1) )?>"
                },
            },
            callbacks: {
                onReady: () => {
                },
                onSubmit: ({ selectedPaymentMethod, formData }) => {

                    formData.external_reference = '<?php _e($order_id)?>';
                    formData.description = '<?php _e($title) ?>';
                    

                    return new Promise((resolve, reject) => {
                        fetch("<?php _e($url)?>", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify(formData),
                        })
                        .then((response) => response.json())
                        .then((response) => {
                // receber o resultado do pagamento
                            if(response.status==true){
                                window.location.href = "?id="+response.id;
                            }
                            if(response.status!=true){
                                alert(response.message);
                            }
                            resolve();
                        })
                        .catch((error) => {
                            reject();
                        });
                    });
                },
                onError: (error) => {
                    console.error(error);
                },
            },
        };
        window.paymentBrickController = await bricksBuilder.create(
            "payment",
            "paymentBrick_container",
            settings
            );
    };

    const renderStatusScreenBrick = async (bricksBuilder) => {
        const settings = {
            initialization: {
                redirectMode: 'modal',
                paymentId: '<?php _e( $id )?>',
            },
            customization: {
                visual: {
                    hideStatusDetails: false,
                    hideTransactionDate: false,
                    style: {
            theme: 'dark', // 'default' | 'dark' | 'bootstrap' | 'flat'
        }
    },
    backUrls: {
        //'error': '<http://<your domain>/error>',
        //'return': '<http://<your domain>/homepage>'
    }
},
callbacks: {
    onReady: () => {
        check(<?php _e( $id )?>);
    },
    onError: (error) => {
    },
},
};
window.statusScreenBrickController = await bricksBuilder.create('statusScreen', 'statusScreenBrick_container', settings);
};

function setupPayment(amount, description) {
    renderPaymentBrick(bricksBuilder, amount, description);
    $('#basicModal').modal('show');
}

<?php if ($id !== ''){ ?>
    //renderStatusScreenBrick(bricksBuilder);
<?php }else{ ?>

<?php if ($this->plan->amount === ''){ ?>

    alert("O valor do pagamento está vazio.");
    
<?php }else{ ?>
       
    var contratarBtn = document.getElementById('contratarBtn');

        contratarBtn.addEventListener('click', function() {
            setupPayment(value, '<?php _e( $title )?>');
    });
<?php } ?>
<?php } ?>

const planInputs = document.querySelectorAll('.plan_by');
const couponInput = document.querySelector('input[name="coupon_code"]');
const paymentSymbol = "<?php _ec( get_option('payment_symbol', '$') )?>";

let finalPaymentValue = 0;
let value = <?php _e( $this->plan->amount )?>;

planInputs.forEach(input => {
    input.addEventListener('change', () => {
        const monthlyPaymentElement = document.querySelector('.by_monthly .text-end.fw-6.fs-18');
        const annuallyPaymentElement = document.querySelector('.by_annually .text-end.fw-6.fs-18');

        if (input.value === '1' && monthlyPaymentElement) {
            finalPaymentValue = parseFloat(monthlyPaymentElement.textContent.trim().replace(paymentSymbol, ''));
        } else if (input.value === '2' && annuallyPaymentElement) {
            finalPaymentValue = parseFloat(annuallyPaymentElement.textContent.trim().replace(paymentSymbol, ''));
        }

        if (couponInput && couponInput.value.trim() !== '') {
            const couponDiscount = 10;
            finalPaymentValue -= (finalPaymentValue * (couponDiscount / 100));
        }

        console.log('Valor final do pagamento:', paymentSymbol + finalPaymentValue.toFixed(2));
        
        value = finalPaymentValue.toFixed(2);
    });
});

var redi = "";
    function check(id) {
        var settings = {
            "url": "<?php _e( base_url() . "/mercadopago/success" )?>",
            "method": "POST",
            "data": JSON.stringify({ "data": { "id": idParameter } }),
            "contentType": "application/json",
            "timeout": 0
        };
        $.ajax(settings).done(function(response) {
            try {
                if (response.status === 'pago') {
                    $("#statusScreenBrick_container").hide();
                    $("#form-pago").slideDown("fast");
                    if (redi != "") {
                        setTimeout(() => {
                            window.location = redi;
                        }, 5000);
                    }
                } else {
                    setTimeout(() => {
                        check(id)
                    }, 3000);
                }
            } catch (error) {
                alert("Erro ao localizar o pagamento, contacte com o suporte");
            }
        });
    }
    function getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[[]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    var idParameter = getParameterByName('id');

    if (idParameter !== null) {
        window.onload = function () {
            $('#basicModal').modal('show');
            renderStatusScreenBrick(bricksBuilder);
        };
    }
    
    function openModal() {
        var modal = document.getElementById('basicModal');
        modal.style.display = 'block';

        window.onclick = function(event) {
            if (event.target == modal) {
                location.reload(); 
            }
        }
    }
</script>
<?php endif ?>
<?php endif ?>