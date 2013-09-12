<?php

class DateFormatter extends Object {
	
	CONST
		DEFAULT_FORMAT = 'j. n. Y';

	public function formatDate($date, $format = NULL) {
		$date = DateTime53::from($date);
		if ($format) {
			$function = 'format' . ucfirst($format);
			return call_user_func(array($this, $function), $date);
		} else {
			return $date->format(self::DEFAULT_FORMAT);
		}
	}

	/**
	 * @TODO other day, not only today
	 */
	public function formatNamedDay(DateTime53 $date) {
		$today = new DateTime53;
		$formatted = $date->format(self::DEFAULT_FORMAT);
		if ($formatted === $today->format(self::DEFAULT_FORMAT)) {
			return 'Dnes';
		} else {
			return $formatted;
		}		
	}

	public function formatShortWeekday(DateTime53 $date) {
		static $names = array('Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So');
		return $names[$date->format('w')];
	}

}