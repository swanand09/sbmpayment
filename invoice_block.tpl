 <script src="{$this_path}assets/js/jquery.loader.js" type="text/javascript"></script>
 <link href="{$this_path}assets/css/jquery.loader.css" rel="stylesheet" />
<script type="text/javascript">
    $(document).ready(function () {
       $('#refundBut').click(function(){
            $.loader({
                    className:"black-with-image",
                    content:'<span style="padding-left:20px">Chargement en cours...</span>'
                });
                var orderAmt;
                orderAmt = $("#refundAmt").val();
                if(orderAmt==''){
                    orderAmt = {$orderAmount}
                }
            $.post(
                 "{$this_path}refund.php",
                  { 
                        id_sbmorder: '{$id_sbmorder}', 
                        orderAmount: orderAmt
                    },
                 
                function(data) {   
                    $.loader('close');
                    if(data.error!="none"){
                        $("#content").prepend('<div class="error"><span style="float:right"><a href="#" id="hideError"><img src="../img/admin/close.png" alt="X"></a></span>'+data.error+'</div>');
                    }else{
                      $("#content").prepend('<div class="conf">'+data.success+'</div>');
                      $("#amtLbl").remove();
                      $("#inptAmtex").remove();
                      $(".center").remove();
                      $("#invoiceBlk").append('<p class="center"> Cette transaction a été remboursée</p>');
                    }
                },'json');
            return false;
        });
         $('#reverseBut').click(function(){
            $.loader({
                    className:"black-with-image",
                      content:'Chargement en cours...'
                });
            $.post(
                 "{$this_path}refund.php",
                  { 
                    id_sbmorder: '{$id_sbmorder}'                       
                  },
                 
                function(data) {   
                  $.loader('close');
                  if(data.error!="none"){
                    $("#content").prepend('<div class="error"><span style="float:right"><a href="#" id="hideError"><img src="../img/admin/close.png" alt="X"></a></span>'+data.error+'</div>');
                  }else{
                    $("#content").prepend('<div class="conf">'+data.success+'</div>');
                    $("#amtLbl").remove();
                    $("#inptAmtex").remove();
                    $(".center").remove();
                    $("#invoiceBlk").append('<p class="center"> Cette transaction a été annulée</p>');
                  }
                },'json');
            return false;
        });
    });
</script> 
<style>
    .sbmLabel{
        width:150px;
        text-align:left;
    }
    .sbm{
           padding: 0 0 1em 150px;
        }
</style>
<fieldset id="invoiceBlk" style="margin-top:50px;">
	<legend>
                <img src="../img/admin/tab-customers.gif"> 
                {l s='Carte de paiement' mod='sbmpayment'}
	</legend>
	<label class="sbmLabel">Nom:</label>{if $cardHoldername !=''}<div class="margin-form sbm">{$cardHoldername}</div>{/if}
	<label class="sbmLabel">Numéro:</label>{if $cardNumber !=''}<div class="margin-form sbm">{$cardNumber}</div>{/if}
        <label class="sbmLabel">Type:</label>{if $cardBrand !=''}<div class="margin-form sbm">{$cardBrand}</div>{/if}
       {if $sbm_orderstatus==2 || $sbm_orderstatus==3}
        <label id="amtLbl" class="sbmLabel">Amount:</label> <div id="inptAmtex" class="margin-form sbm"><input type="text" name="refundAmt" id="refundAmt" value /></div>
        <p class="center"> 
             <input type="button" value="{l s='Rembourser transaction' mod='sbmpayment'}" class="button" name="refundBut" id="refundBut" />&nbsp; 
              <input type="button" value="{l s='Annuler transaction' mod='sbmpayment'}" class="button" name="reverseBut" id="reverseBut" />
       </p>
       {else if $sbm_orderstatus==4}
           <p class="center"> Cette transaction a été déja remboursée</p>
       {else if $sbm_orderstatus==6||$sbm_orderstatus==''}
           <p class="center">Cette carte ne semble pas valide</p>
       {else if $sbm_orderstatus==0 ||$sbm_orderstatus==1}   
           <p class="center">Aucun paiement n'a été fait actuellement</p>
       {/if}    
</fieldset>