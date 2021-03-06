<?php namespace Fisharebest\Localization\Territory;

/**
 * Class AbstractTerritory - Representation of the territory PE - Peru.
 *
 * @author    Greg Roach <fisharebest@gmail.com>
 * @copyright (c) 2015 Greg Roach
 * @license   GPLv3+
 */
class TerritoryPe extends AbstractTerritory implements TerritoryInterface {
	public function code() {
		return 'PE';
	}

	public function firstDay() {
		return 0;
	}
}
