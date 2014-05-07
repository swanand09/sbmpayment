{capture name=path}{l s='Shipping'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
<h2>{l s='Paiement' mod='sbmpayment'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}
<script src="{$base_dir}js/jquery/plugins/jquery.validate-creditcard.js" type="text/javascript"></script>
 <script src="{$modules_dir}sbmpayment/assets/js/jquery.loader.js" type="text/javascript"></script>
  <script src="{$modules_dir}sbmpayment/assets/js/jquery.validationEngine.js" type="text/javascript"></script>
 <script src="{$modules_dir}sbmpayment/assets/js/languages/jquery.validationEngine-fr.js" type="text/javascript"></script>
 <link href="{$modules_dir}sbmpayment/assets/css/validationEngine.jquery.css" rel="stylesheet" />
 <link href="{$modules_dir}sbmpayment/assets/css/jquery.loader.css" rel="stylesheet" />
<script type="text/javascript">
    
    $(document).ready(function () {
        {literal}
        $("#cardDetFrm").validationEngine({
        'custom_error_messages': {
         '#cardNumber': {
          'minSize': {'message' :" * 16 caractères requis"},
          'maxSize': {'message' :" * 16 caractères requis"}
         },
          '#cvc': {
          'minSize': {'message' :" * 3 caractères requis"},
          'maxSize': {'message' :" * 3 caractères requis"}
         }
        }
       });
        {/literal}
     // var validateExpDate = function( field, rules, i, options ){
      
       

        $("#cardDetFrm").submit(function(){
             
            if($(this).validationEngine('validate')){     
                $.loader({
                        className:"blue-with-image",
                        content:'Chargement en cours...'
                    });
                $.post(
                     "{$this_path_ssl}validation.php",
                      { 
                            cardBrand:      $("#cardBrand").val(), 
                            cardholderName: $("#cardholderName").val(),
                            cardNumber:     $("#cardNumber").val(),
                            cvc:            $("#cvc").val(),
                            sbmOrderId:     $("#sbmOrderId").val(),
                            cardExpiration: $("select[name='expDate_Year']").val()+$("select[name='expDate_Month']").val()

                        },
                 
                function(data) {   
                    $.loader('close');
                   if(data.error=="none"){
                         $(location).attr('href',data.redirectUrl);
                   }else{
                      $(".error" ).css('display','block').html(data.error);  
                   }
                },'json');
            
            }
            return false;
        });
    });
   
 function validateExpDate(field, rules, i, options){
        var today = new Date();
        var currentYear = (new Date).getFullYear();

        //convert currentYear to string and assign to var twoDigit
        var twoDigit = currentYear + "";

        //Get last two digit ( characters )
       // twoDigit = twoDigit.substr(twoDigit.length - 2);

        //current month
        var mm = today.getMonth()+1;

        //select month
        var sm = +(field.val());

        if( sm < mm && $('select[name="expDate_Year"]').val() == twoDigit ) {
          return "La date n'est pas valide";
        }
    }   
  function validateCreditCard(){
     if(!validateCC($("#cardNumber").val(),$("#cardBrand").val())){        
         return "Votre "+$("#cardBrand").val()+" n\'est pas valide.";        
    }
  }
</script>   

<form action="#" method="post" id="cardDetFrm">
 <fieldset class="account_creation">   
     <h3>{l s='Détails de la carte de paiement' mod='sbmpayment'}</h3>
<input type="hidden" name="sbmOrderId" id="sbmOrderId" value="{$sbmOrderId}" />
<div class="error" style="display:{$errCss}">   
    {$msgError}
</div>
<p class="required text">
    <label style="margin-right:50px;">{l s='Nom:' mod='sbmpayment'} <sup>*</sup></label>
     <input type="text" name="cardholderName" class="validate[required]" id="cardholderName" value="{$cardholderName}" class="text" />    
</p>
<p class="select">
   <span style="margin-right:12px;">{l s='Type de carte:' mod='sbmpayment'}
</span>
    <select name="cardBrand" id="cardBrand">
        <option value="MasterCard">MasterCard</option>
        <option value="VISA">VISA</option>
    </select>
</p>   
<p class="required text">
    <label style="margin-right:32px;">{l s='Numéro:' mod='sbmpayment'} <sup>*</sup></label>
    <input type="text" name="cardNumber" id="cardNumber" class="validate[ required, custom[integer], minSize[16], funcCall[validateCreditCard]]" size="16" maxlength="16"  value="{$cardNumber}" class="text"/>
</p> 
<p class="required text">
    <label style="margin-right:60px;">{l s='cvc:' mod='sbmpayment'} <sup>*</sup></label>
    <input type="text" class="validate[ required, custom[integer], minSize[3]]" name="cvc" class="validate[required]"  maxlength="3" id="cvc" value="{$cvc}" />    
</p> 
<p class="select">
   <span style="margin-right:3px;">{l s='Date expiration:' mod='sbmpayment'}</span>
   {html_select_date 
    prefix='expDate_' 
    start_year='-0'
    end_year='+15' 
    display_days=false
    display_years = false
    month_empty="Month"
  all_extra = "class='validate[required,funcCall[validateExpDate]]'"
   }{*  all_extra = "class='validate[required,funcCall[validateExpDate]]'"*}
 {html_select_date 
    prefix='expDate_' 
    start_year='-0'
    end_year='+15' 
    display_days=false
    display_months = false
    year_empty="Year"     
   }
</p>   
	
<p class="cart_navigation">
	<a href="{$base_dir_ssl}order.php?step=3" class="button_large">{l s='Retour' mod='sbmpayment'}</a>
	<input type="submit" name="paymentSubmit" value="{l s='Valider' mod='creditcard'}" class="exclusive_large" />
</p>
 </fieldset
</form>