<?php

$rawcsv = file_get_contents('http://data.gov.ru/opendata/7708660670-proizvcalendar/data-20161107T1038-structure-20161107T1038.csv?encoding=UTF-8');
$csvlines = preg_replace('/^([0-9]{4}),/', '"${1}",', split("\n", str_replace("\r", "", $rawcsv)));
$dayMap = array();

foreach ($csvlines as $csvline) {
	$csvwords = str_getcsv($csvline);
	$year = $csvwords[0];
	unset($csvwords[0]);

	if (strlen($year) != 4) {
		continue;
	}

	foreach ($csvwords as $_month => $daysraw) {
		if ($_month > 12) {	// $month > 12 is a statitics, not real months
			break;
		}
		$month = sprintf('%02d', $_month);
		$days = split(",", $daysraw);

		// Filling the month by workdays
		$days_in_month = @cal_days_in_month(CAL_GREGORIAN, (int)$_month, (int)$year);
		$day = 0;
		while ($day++ < $days_in_month) {
			$dayMap["$year-$month-".sprintf("%02d", $day)] = 2;
		}

		// Parsing holidays and short days
		foreach ($days as $day) {
			$isShortDay = substr($day, strlen($day)-1, 1) == "*";
			if ($isShortDay) {
				$day = substr($day, 0, strlen($day)-1);
			}
			$day = sprintf('%02d', $day);
			$date = "$year-$month-$day";
			if ($isShortDay) {
				$dayMap[$date] = 1;
			} else {
				$dayMap[$date] = 0;	// Holiday
			}
		}
	}
}

print "START TRANSACTION;\n";
foreach ($dayMap as $date => $dayType) {
	print 'INSERT IGNORE INTO `prodcalendar` (`doc`, `date`, `isprod`, `isfilled`) VALUES (NOW(), "'.$date.'", "'.$dayType.'", 1);'."\n";
}
print "COMMIT;\n";

?>
