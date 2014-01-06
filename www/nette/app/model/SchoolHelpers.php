<?php
/**
 * Created by JetBrains PhpStorm.
 * User: matej
 * Date: 6.1.14
 * Time: 11:09
 * To change this template use File | Settings | File Templates.
 */

class SchoolHelpers {


	/**
	 * get current end of school year
	 * for month > 9 return current year + 1, otherwise current year
	 * @return number
	 */
	public static function getCurrentSchoolYear() {
		$year = date('Y');
		$month = date('m');
		if ($month >= 9) {
			$year++;
		}
		return $year;
	}

}