<?php

namespace Sw2;

use Nette;
use Nette\Utils\Strings;

/**
 * Class LatteControl
 *
 * @package Sw2\Controls
 */
abstract class LatteControl extends Nette\Application\UI\Control
{

	/**
	 * @param string $methodName
	 * @param array $args
	 *
	 * @return mixed
	 * @throws Nette\Application\BadRequestException
	 */
	public function __call($methodName, $args)
	{
		if (Strings::startsWith($methodName, 'render')) {
			$subMethod = lcfirst(Strings::replace($methodName, '~render(\w*)~', '\\1'));

			$files = $this->formatTemplateFiles($subMethod);
			foreach ($files as $file) {
				if (is_file($file)) {
					$this->template->setFile($file);
					break;
				}
			}

			if (!$this->template->getFile()) {
				$file = preg_replace('#^.*([/\\\\].{1,70})\z#U', "\xE2\x80\xA6\$1", reset($files));
				$file = strtr($file, '/', DIRECTORY_SEPARATOR);
				throw new Nette\Application\BadRequestException("Page not found. Missing template '$file'.");
			}

			$this->tryCall("prepare$subMethod", (array)@$args[0]);
			$this->tryCall('beforeRender', []);
			$this->template->render();
		}
		else {
			return parent::__call($methodName, $args);
		}
	}

	/**
	 * Formats view template file names.
	 *
	 * @param string $subMethod
	 * @return array
	 */
	protected function formatTemplateFiles($subMethod)
	{
		$controlName = $this->getReflection()->getShortName();
		$control = Strings::replace($controlName, '~Control$~');
		$dir = dirname($this->getReflection()->getFileName());
		$dir = is_dir("$dir/templates") ? $dir : dirname($dir);
		$view = $subMethod ?: 'default';

		return [
			"$dir/templates/$control/$view.latte",
			"$dir/templates/$control.$view.latte",
		];
	}

}
