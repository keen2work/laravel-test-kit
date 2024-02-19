<?php

namespace EMedia\TestKit\Testbench;

use Illuminate\Support\Facades\File;

trait BacksUpApplicationDir
{

	protected function getBasePathBackupDir(): string
	{
		return base_path().'/../laravel_backup/';
	}

	protected function backupApplicationRoot(): void
	{
		$backupDir = $this->getBasePathBackupDir();

		// We only need to backup if we don't have a backup already
		// We shouldn't overwrite an existing backup, because existing source might be dirty
		if (!file_exists($backupDir)) {
			File::copyDirectory(base_path(), $backupDir);
		}
	}

	protected function restoreApplicationRoot(): void
	{
		$backupDir = $this->getBasePathBackupDir();

		// if we have a backup, then restore it
		if (file_exists($backupDir)) {
			File::copyDirectory($backupDir, base_path());
		}
	}

	protected function deleteBasePathBackup(): void
	{
		File::deleteDirectory($this->getBasePathBackupDir());
	}
}
