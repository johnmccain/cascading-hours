<?php

function cascading_hours_block_cmp($a, $b)
{
    return strtotime($a['start']) - strtotime($b['start']);
}

function test_cascading_hours_block_cmp()
{
	//future timstamp
	$a['start'] = date(DATE_ATOM, strtotime('tomorrow'));
	//present timestamp
	$b['start'] = date(DATE_ATOM, strtotime('now'));
	//past timestamp
	$c['start'] = date(DATE_ATOM, strtotime('yesterday'));

	echo "<p>a: " . json_encode($a) . "</p><p>b: " . json_encode($b) . "</p><p>c: " . json_encode($c) . "</p>";

	echo '<ul>';
	$result = (0 < cascading_hours_block_cmp($a, $b)); //should be positive
	$message = '(tomorrow, now) should return a positive value.';
	assertTrue(0 < cascading_hours_block_cmp($a, $b), $message);
	echo '<li><h6>' . var_dump(strtotime($a['start'])) . ' - ' . var_dump(strtotime($b['start'])) . ' = ' . (strtotime($a['start']) - strtotime($b['start']) ). '</h6></li>';

	$result = (0 > cascading_hours_block_cmp($b, $a)); //should be negative
	$message = '(now, tomorrow) should return a negative value.';
	assertTrue(cascading_hours_block_cmp($b, $a), $message);

	echo '<li><h6>strtotime($b[\'start\']) - strtotime($a[\'start\']) = ' .( strtotime($b['start']) - strtotime($a['start']) ). '</h6></li>';


	$result = (0 < cascading_hours_block_cmp($a, $c)); //should be positive
	$message = '(tomorrow, yesterday) should return a positive value.';
	assertTrue(cascading_hours_block_cmp($a, $c), $message);

	$result = (0 > cascading_hours_block_cmp($c, $b)); //should be negataive
	$message = '(yesterday, now) should return a negative value.';
	assertTrue(0 > cascading_hours_block_cmp($c, $b), $message);

	$result = (0 == cascading_hours_block_cmp($b, $b)); //should be positive
	$message = '(now, now) should return 0.';
	assertTrue($result, $message);

	echo '</ul>';
}

function assertTrue($result, $message) {
	if($result) {
		echo '<li>Passed: ' . $message . '</li>';
	} else {
		echo '<li><b style="color:red;">' . $message . '; result: ' . var_dump($result) . '</b></li>';
	}
}

test_cascading_hours_block_cmp();
