<?php namespace Fisharebest\Localization;

/**
 * Class LocaleCaAd
 *
 * @author        Greg Roach <fisharebest@gmail.com>
 * @copyright (c) 2015 Greg Roach
 * @license       GPLv3+
 */
class LocaleCaAd extends LocaleCa {
	/** {@inheritdoc} */
	public function territory() {
		return new TerritoryAd;
	}
}