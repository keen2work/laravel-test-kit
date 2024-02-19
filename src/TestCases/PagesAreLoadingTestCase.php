<?php
namespace EMedia\TestKit\TestCases;

abstract class PagesAreLoadingTestCase extends TestCase
{

	/**
	 *
	 * Return a list of urls and text on the pages to check
	 *
	 * @return string[]
	 */
	protected function getPublicPages(): array
	{
		return [
			'/'     => 'Welcome',
			'/contact-us' => 'Contact Us'
		];
	}

	// quick test to see if all pages are loading
	public function testPublicPagesAreLoading(): void
	{
		$publicPagesMap = $this->getPublicPages();

		foreach ($publicPagesMap as $url => $textToSee) {
			$this->visit($url)->see($textToSee);
		}
	}
}
