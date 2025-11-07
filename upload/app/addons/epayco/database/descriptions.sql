INSERT INTO `cscart_page_descriptions` (`page_id`, `lang_code`, `page`, `description`, `meta_keywords`, `meta_description`, `page_title`, `link`) 
VALUES (201, 'es', 'checkout epayco', 
'<style>
 .epayco-title{
 max-width: 900px;
 display: block;
 margin:auto;
 color: #444;
 font-weight: 700;
 margin-bottom: 25px;
 }
 .loader-container{
 position: relative;
 padding: 20px;
 color: #ff5700;
 }
 .epayco-subtitle{
 font-size: 14px;
 }
 .epayco-button-render{
 transition: all 500ms cubic-bezier(0.000, 0.445, 0.150, 1.025);
 transform: scale(1.1);
 box-shadow: 0 0 4px rgba(0,0,0,0);
 }
 .epayco-button-render:hover {
 transform: scale(1.2);
 }
 .animated-points::after{
 content: "";
 animation-duration: 2s;
 animation-fill-mode: forwards;
 animation-iteration-count: infinite;
 animation-name: animatedPoints;
 animation-timing-function: linear;
 position: absolute;
 }
 .animated-background {
 animation-duration: 2s;
 animation-fill-mode: forwards;
 animation-iteration-count: infinite;
 animation-name: placeHolderShimmer;
 animation-timing-function: linear;
 color: #f6f7f8;
 background: linear-gradient(to right, #7b7b7b 8%, #999 18%, #7b7b7b 33%);
 background-size: 800px 104px;
 position: relative;
 background-clip: text;
 -webkit-background-clip: text;
 -webkit-text-fill-color: transparent;
 }
 .loading::before{
 -webkit-background-clip: padding-box;
 background-clip: padding-box;
 box-sizing: border-box;
 border-width: 2px;
 border-color: currentColor currentColor currentColor transparent;
 position: absolute;
 margin: auto;
 top: 0;
 left: 0;
 right: 0;
 bottom: 0;
 content: " ";
 display: inline-block;
 background: center center no-repeat;
 background-size: cover;
 border-radius: 50%;
 border-style: solid;
 width: 30px;
 height: 30px;
 opacity: 1;
 -webkit-animation: loaderAnimation 1s infinite linear,fadeIn 0.5s ease-in-out;
 -moz-animation: loaderAnimation 1s infinite linear, fadeIn 0.5s ease-in-out;
 animation: loaderAnimation 1s infinite linear, fadeIn 0.5s ease-in-out;
 }
 @keyframes animatedPoints{
 33%{
 content: "."
 }
 66%{
 content: ".."
 }
 100%{
 content: "..."
 }
 }
 @keyframes placeHolderShimmer{
 0%{
 background-position: -800px 0
 }
 100%{
 background-position: 800px 0
 }
 }
 @keyframes loaderAnimation{
 0%{
 -webkit-transform:rotate(0);
 transform:rotate(0);
 animation-timing-function:cubic-bezier(.55,.055,.675,.19)
 }
 50%{
 -webkit-transform:rotate(180deg);
 transform:rotate(180deg);
 animation-timing-function:cubic-bezier(.215,.61,.355,1)
 }
 100%{
 -webkit-transform:rotate(360deg);
 transform:rotate(360deg)
 }
 }
 </style>
<div> 
 <div class="loader-container">
 <div class="loading"></div>
 </div>
 <p style="text-align: center;" class="epayco-title">
 <span class="animated-points">Cargando métodos de pago</span>
 <br><small class="epayco-subtitle">Si no se cargan automáticamente, de clic en el botón "Pagar con ePayco</small>
 </p>
    </div>
<p> </p>
<p></p>
<p></p>
<center>
<br> 
<a id="btn_epayco" href="#">
<br> 
<img src="https://cdn.pixabay.com/photo/2015/07/25/08/05/the-button-859351_1280.png">
<br> 
</a>
<br> 
</center>
<p></p>
<p></p>
<script src="https://epayco-checkout-testing.s3.amazonaws.com/checkout.preprod-v2.js"></script>
<script>
// Obtener parámetros de la URL
function getQueryParam(param) {
        let params = new URLSearchParams(window.location.search);
        return params.get(param);
}

document.addEventListener('DOMContentLoaded', function() {
        var p_public_key = getQueryParam('key');
        var test = getQueryParam('test');
        var order_id = getQueryParam('order_id');
        var currency = getQueryParam('currency');
        var total = getQueryParam('total');
        var tax = getQueryParam('tax');
        var sub_total = getQueryParam('sub_total');
        var country = getQueryParam('country');
        var lang = getQueryParam('lang');
        var external = getQueryParam('external');
        var url = window.location.origin+window.location.pathname+"?dispatch=";
        var confirmationUrl = url.replace("checkout-epayco/", "index.php")+"payment_notification.confirmation&payment=epayco&order_id="+order_id;
        var responseUrl = url.replace("checkout-epayco/", "index.php")+"payment_notification.response&payment=epayco&order_id="+order_id;

        // Configuración para el nuevo widget v2
        window.epayco.checkoutV2.init({
                key: p_public_key,
                test: test === 'true' || test === '1',
                payment: {
                        name: "Order #"+order_id,
                        description: "Order #"+order_id,
                        invoice: order_id,
                        currency: currency,
                        amount: total,
                        tax_base: sub_total,
                        tax: tax,
                        country: country,
                        lang: lang,
                        external: external,
                        extra1: order_id,
                        confirmation: confirmationUrl,
                        response: responseUrl
                },
                onReady: function() {
                        // Opcional: ocultar loader, mostrar botón, etc.
                },
                onError: function(error) {
                        alert('Error al cargar ePayco: ' + error.message);
                }
        });

        // Botón para abrir el checkout manualmente
        var btn = document.getElementById('btn_epayco');
        if (btn) {
                btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        window.epayco.checkoutV2.open();
                });
        }

        // Abrir automáticamente el checkout
        window.epayco.checkoutV2.open();
});
</script>
'checkout epayco', 'checkout epayco', 'checkout epayco', '');