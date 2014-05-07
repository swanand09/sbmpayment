{if $status == 'OK'}
<p>{l s='Votre commande a été un succés sur %s' sprintf=$shop_name mod='sbmpayment'}
		<br /><br />
		
		<br /><br />- {l s='Montant' mod='sbmpayment'} <span class="price"> <strong>{$total_to_pay}</strong></span>
		
		<br /><br />{l s='Un mail vous a été envoyé.' mod='sbmpayment'}
		<br /><br />{l s='Si vous avez des questions, veuillez contacter notre' mod='sbmpayment'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s=' equipe de support. ' mod='sbmpayment'}</a>.
	</p>
{else if $status == 'OUTOFSTOCK'}
    <p class="warning">
		{l s="Votre commande est prise en compte mais le produit est hors stock. Veuillez contacter le " mod='sbmpayment'} 
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='support.' mod='sbmpayment'}</a>.
	</p>
{else}
	<p class="warning">
		{l s="Une erreur au niveau de paiement a été detectée et la commande n'a pas passé. Veuillez contacter le " mod='sbmpayment'} 
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='support.' mod='sbmpayment'}</a>.
	</p>
{/if}