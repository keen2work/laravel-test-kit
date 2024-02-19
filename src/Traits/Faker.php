<?php
namespace EMedia\TestKit\Traits;

use Faker\Factory;

trait Faker
{

	// faker
	public function getFaker($region = 'en_AU')
	{
		return Factory::create($region);
	}
}
