<?php

namespace Microsite\Application;

use LeanQuery\DomainQueryFactory;
use Microsite\Site\IContentsFormFactory;
use Microsite\Site\Page;

/**
 * @author Vojtěch Kohout
 */
class AdminPresenter extends Presenter
{

	/** @var DomainQueryFactory */
	private $domainQueryFactory;

	/** @var IContentsFormFactory */
	private $contentsFormFactory;

	/** @var Page */
	private $updatedPage;


	/**
	 * @param DomainQueryFactory $domainQueryFactory
	 * @param IContentsFormFactory $contentsFormFactory
	 */
	public function __construct(DomainQueryFactory $domainQueryFactory, IContentsFormFactory $contentsFormFactory)
	{
		$this->domainQueryFactory = $domainQueryFactory;
		$this->contentsFormFactory = $contentsFormFactory;
	}


	public function startup()
	{
		parent::startup();
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:');
		}
	}

	/**
	 * @param int $pageId
	 */
	public function actionUpdate($pageId)
	{
		$this->updatedPage = $this->domainQueryFactory->createQuery()
			->select('p')
			->from('Microsite\Site\Page', 'p') // you can use Page::class instead of string in PHP 5.5
			->leftJoin('p.contents', 'c')->select('c')
			->where('p.id = %i AND p.lang = %s', $pageId, $this->lang->id)
			->orderBy('c.ord')
			->getEntity();

		if ($this->updatedPage === null) {
			$this->error("Stránka s ID $pageId nebyla nalezena.");
		}

		$this['contentsForm'] = $contentsForm = $this->contentsFormFactory->create($this->updatedPage->contents);

		$contentsForm->onSuccess[] = function () {
			$this->flashMessage('Změny byly úspěšně uloženy.', 'success');
			$this->redirect('this');
		};
	}


	public function actionSignOut()
	{
		$this->getUser()->logout();
		$this->redirect('this');
	}


	public function renderDefault()
	{
		$this->template->pages = $this->domainQueryFactory->createQuery()
			->select('p')
			->from('Microsite\Site\Page', 'p') // you can use Page::class instead of string in PHP 5.5
			->where('p.lang = %s', $this->lang->id)
			->orderBy('p.ord')
			->getEntities();
	}


	public function renderUpdate()
	{
		$this->template->updatedPage = $this->updatedPage;
	}

}
