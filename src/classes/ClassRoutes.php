<?php
namespace Src\classes;

use Src\traits\TraitUrlParser;
class ClassRoutes{

use TraitUrlParser;

	private $Rotas;

	public function getRota()
	{
		$url=$this->parseUrl();
		$I=$url[0];

		$this->Rotas= array(
			""=>"ControllerHome",
			"home"=>"ControllerHome",
			"login"=>"ControllerAuth",
			"register"=>"ControllerAuth",
			"authenticate"=>"ControllerAuth",
			"store"=>"ControllerAuth",
			"logout"=>"ControllerAuth",
			"recover"=>"ControllerAuth",
			"reset"=>"ControllerAuth",
			"verify"=>"ControllerAuth",
			"properties"=>"ControllerProperty",
			"property"=>"ControllerProperty",
			"featured"=>"ControllerProperty",
			"agency"=>"ControllerProperty",
			"dashboard"=>"ControllerDashboard",
			"requests"=>"ControllerDashboard",
			"commissions"=>"ControllerDashboard",
			"referrals"=>"ControllerDashboard",
			"profile"=>"ControllerDashboard",
			"moderate"=>"ControllerProperty",
			"moderate_users"=>"ControllerDashboard",
			"request"=>"ControllerRequest",
			"payment_methods"=>"ControllerPayment",
			"payment_channels"=>"ControllerPayment",
			"payment_accounts"=>"ControllerDashboard",
			"payment_transactions"=>"ControllerPayment",
			"settings"=>"ControllerDashboard",
			"admin_subscriptions"=>"ControllerDashboard",
			"favorites"=>"ControllerDashboard",
			"cookies"=>"ControllerLegal",
			"sitemap"=>"ControllerSitemap",
			"notification"=>"ControllerNotification",
			"api"=>"ControllerApi",
			"file"=>"ControllerFile",
			// ADD YOUR ROUTES HERE
		);

		if (array_key_exists($I, $this->Rotas)) {
			if(file_exists(DIRREQ."app/controller/{$this->Rotas[$I]}.php"))
			{
				return $this->Rotas[$I];
			}
			else{return "ControllerHome";}
		}
		else
		{
			return "Controller404";
		}
	} 
}
?>