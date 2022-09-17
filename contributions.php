<?php
require('lib/common.php');

$id = $_GET['id'] ?? null;

$user = fetch("SELECT * FROM users WHERE id = ?", [$_GET['id']]);

if (!$user) error('404', 'Invalid user.');

$stats = [
	'contributions' => 0,
	'additions' => 0,
	'removals' => 0,
];

$revisions = query("SELECT id, size, sizediff FROM wikirevisions WHERE author = ?", [$id]);

while ($rev = $revisions->fetch()) {
	$stats['contributions']++;
	if ($rev['sizediff'] > 0) {
		// positive sizediff - add to additions
		$stats['additions'] += $rev['sizediff'];
	} else if ($rev['sizediff'] < 0) {
		// negative sizediff - add to removals
		$stats['removals'] += -$rev['sizediff'];
	} else {
		// 0 sizediff - page is new, add entire revision size to additions
		$stats['additions'] += $rev['size'];
	}
}

$contrib_text = sprintf(
	'%s has made %d contributions (<strong><span style="color:#00ff00">%s bytes added</span>, <span style="color:#ff0000">%s bytes removed</span></strong>)',
userlink($user), $stats['contributions'], number_format($stats['additions'], 0, '', ' '), number_format($stats['removals'], 0, '', ' '));

$contributions = query("SELECT page, revision, size, sizediff, time, description FROM wikirevisions WHERE author = ? ORDER BY time DESC, id DESC LIMIT 50", [$id]);

$twig = _twigloader();
echo $twig->render('contributions.twig', [
	'id' => $id,
	'user' => $user,
	'contrib_text' => $contrib_text,
	'contributions' => $contributions
]);
