<?php
namespace EMedia\TestKit\Traits;

trait InteractsWithUsers
{

	protected $userClass = '\App\Models\User';

	protected function setUserClass($class): self
	{
		$this->userClass = $class;

		return $this;
	}

	protected function getUserClass(): string
	{
		return $this->userClass;
	}

	protected function getUserModel()
	{
		return app()->make($this->getUserClass());
	}

	/**
	 *
	 * Find a user for the given role
	 *
	 * @param $roleName
	 *
	 * @return mixed
	 */
	protected function findUserByRole($roleName)
	{
		$user = $this->getUserModel()::whereHas('roles', function ($q) use ($roleName) {
			$q->where('name', $roleName);
		})->first();

		if (!$user) {
			throw new \InvalidArgumentException("A user not found for the role `$roleName`.");
		}

		return $user;
	}

	/**
	 *
	 * Get a user who doesn't a given role
	 *
	 * @example
	 * $this->findUserWithoutRole('admin');
	 *
	 * @param $roleNames
	 *
	 * @return mixed
	 */
	protected function findUserWithoutRole($roleNames)
	{
		$roleNames = func_get_args();
		if ((func_num_args() == 1) && is_array($roleNames[0])) {
			$roleNames = $roleNames[0];
		}

		$query = $this->getUserModel()::query();

		foreach ($roleNames as $roleName) {
			$query->doesntHave('roles', 'and', function ($q) use ($roleName) {
				$q->where('name', $roleName);
			});
		}

		return $query->first();
	}

	/**
	 *
	 * Get a user who doesn't have the given roles
	 *
	 * @example
	 * $this->findUserWithoutRole('admin', 'super-admin');
	 * $this->findUserWithoutRole(['admin', 'super-admin']);
	 *
	 * @param $roleNames
	 *
	 * @return mixed
	 */
	protected function findUserWithoutRoles($roleNames)
	{
		return $this->findUserWithoutRole($roleNames);
	}


	/**
	 *
	 * Find a user by a given email
	 *
	 * @param $email
	 *
	 * @return mixed
	 */
	protected function findUserByEmail($email)
	{
		$users = $this->getUserModel()::whereEmail($email)->get();

		if ($users->isEmpty()) {
			throw new \InvalidArgumentException("A user not found for the email `$email`.");
		}

		return $users->first();
	}
}
