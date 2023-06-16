<?php
declare(strict_types=1);

namespace NgLam2911\ShopUI\forms;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use Generator;
use NgLam2911\libasyneco\exceptions\EcoException;
use NgLam2911\ShopUI\elements\Category;
use NgLam2911\ShopUI\Loader;
use NgLam2911\ShopUI\utils\Utils;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

readonly class CategoryForm extends AsyncForm{

	public function __construct(
		private Category $category,
		private Player $player,
		private ?CategoryForm $callback = null
	){
		$this->send();
	}

	protected function asyncSend() : Generator{
		try{
			$balance = yield from Loader::getInstance()->ecoProvider->myMoney($this->player);
		}catch(EcoException){
			Loader::getInstance()->getLogger()->error("Cant get balance infomation from user: " . $this->player->getName());
			$this->player->sendMessage("Something went wrong...");
			return;
		}
		$onSumbit = fn(Player $player, int $selectedOption) => $this->handleSelection($player, $selectedOption);
		$menuOptions = array_map(fn($element) => Utils::parseElement2MenuOption($element), $this->category->getItems());
		$exitButton = is_null($this->callback)?(new MenuOption("Exit")):(new MenuOption("Back"));
		$menuOptions = array_merge([$exitButton], $menuOptions);
		$form = new MenuForm(
			$this->category->getName(),
			"Your balance: " . $balance . "$",
			$menuOptions,
			$onSumbit,
			null,
		);
		$this->player->sendForm($form);
	}

	public function handleSelection(Player $player, int $selectedOption) : void{
		if ($selectedOption === 0){ //Exit
			if (!is_null($this->callback)){
				$this->callback->send();
			}
			return;
		}
		$element = $this->category->getItems()[$selectedOption - 1];
		if ($element instanceof Category)
			new CategoryForm($element, $player, $this);
	}



}