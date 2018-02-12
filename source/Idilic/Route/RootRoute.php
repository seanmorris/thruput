<?php
namespace SeanMorris\ThruPut\Idilic\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function clear($router)
	{
		$cacheDir = new \SeanMorris\Ids\Disk\Directory(
			$this->cachePath()
		);

		while($file = $cacheDir->read())
		{
			printf('Removing %s...' . PHP_EOL, $file->name());
			$file->delete();
		}
	}

	public function cachePath()
	{
		return sprintf(
			'%s/../temporary/thruput/'
			, IDS_VENDOR_ROOT
		);
	}
}
