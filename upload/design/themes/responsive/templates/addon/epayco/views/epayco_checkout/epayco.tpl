{** Smarty template for mi_pagina **}
<div class="loader-container">
    <div class="loading"></div>
</div>
<p style="text-align: center;" class="epayco-title">
    <span class="animated-points">{$msgEpaycoCheckout}</span>
    <br><small class="epayco-subtitle"> {$msgEpaycoCheckoutDescription}</small>
</p>
<center>
    <a id="btn_epayco" href="#">
        <img src="{$url_button|escape:'htmlall':'UTF-8'}">
    </a>
</center>
<form id="epayco_form" style="text-align: center;">
    <script src="https://epayco-checkout-testing.s3.amazonaws.com/checkout.preprod.js"></script>
    <script>
        var handler = ePayco.checkout.configure({
            key: "{$p_public_key}",
            test: "{$test_request}"
        })
        var date = new Date().getTime();
        var extras_epayco = {
            extra5:"P30"
        };
        var data = {
            name: "{$p_description}",
            description: "{$p_description}",
            invoice: "{$order_id|escape:'htmlall':'UTF-8'}",
            currency: "{$currency_code|lower|escape:'htmlall':'UTF-8'}",
            amount: "{$amount|escape:'htmlall':'UTF-8'}".toString(),
            tax_base: "{$amount_base|escape:'htmlall':'UTF-8'}".toString(),
            tax: "{$tax|escape:'htmlall':'UTF-8'}".toString(),
            taxIco: "0",
            country: "{$shipCountry|lower|escape:'htmlall':'UTF-8'}",
            lang: "{$lang|escape:'htmlall':'UTF-8'}",
            external: "{$type_checkout_mode|escape:'htmlall':'UTF-8'}",
            confirmation: "{$url_confirmation|unescape: 'html' nofilter}",
            response: "{$url_response|unescape: 'html' nofilter}",
            name_billing: "",
            address_billing: "{$billAddress|escape:'htmlall':'UTF-8'}",
            email_billing: "{$payerEmail|escape:'htmlall':'UTF-8'}",
            extra1: "{$order_id|escape:'htmlall':'UTF-8'}",
            autoclick: "true",
            ip:  "{$ip|escape:'htmlall':'UTF-8'}",
            test: "{$test_request|escape:'htmlall':'UTF-8'}".toString(),
            extras_epayco: extras_epayco
        }
        const apiKey = "{$p_public_key}";
        const privateKey = "{$p_private_key}";
        var openChekout = function () {
            if(localStorage.getItem("invoicePayment") == null){
                localStorage.setItem("invoicePayment", data.invoice);
                makePayment(privateKey,apiKey,data, data.external == "true"?true:false)
            }else{
                if(localStorage.getItem("invoicePayment") != data.invoice){
                    localStorage.removeItem("invoicePayment");
                    localStorage.setItem("invoicePayment", data.invoice);
                    makePayment(privateKey,apiKey,data, data.external == "true"?true:false)
                }else{
                    makePayment(privateKey,apiKey,data, data.external == "true"?true:false)
                }
            }
        }
        var makePayment = function (privatekey, apikey, info, external) {
            const headers = { "Content-Type": "application/json" } ;
            headers["privatekey"] = privatekey;
            headers["apikey"] = apikey;
            var payment =   function (){
                return  fetch("https://cms.epayco.io/checkout/payment/session", {
                    method: "POST",
                    body: JSON.stringify(info),
                    headers
                })
                    .then(res =>  res.json())
                    .catch(err => err);
            }
            payment()
                .then(session => {
                    if(session.data.sessionId != undefined){
                        localStorage.removeItem("sessionPayment");
                        localStorage.setItem("sessionPayment", session.data.sessionId);
                        const handlerNew = window.ePayco.checkout.configure({
                            sessionId: session.data.sessionId,
                            external: external,
                        });
                        handlerNew.openNew()
                    }
                })
                .catch(error => {
                    error.message;
                });
        }
        var bntPagar = document.getElementById("btn_epayco");
        bntPagar.addEventListener("click", openChekout);
        openChekout()
    </script>
</form>
<script language="Javascript">
    const app = document.getElementById("epayco_form");
    window.onload = function() {
        document.addEventListener("contextmenu", function(e){
            e.preventDefault();
        }, false);
    }
</script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    $(document).keydown(function (event) {
        if (event.keyCode == 123) {
            return false;
        } else if (event.ctrlKey && event.shiftKey && event.keyCode == 73) {
            return false;
        }
    });
</script>