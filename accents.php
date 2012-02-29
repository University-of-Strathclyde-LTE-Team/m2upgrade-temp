<?php 

	/*
	 * Replaces old Javascript accented character selector with new 
	 * filter version.
	 * 
	 */

	if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
		$test = false;	
	} else {
		$test = true;
	}
	
	if($test) {
?>
<style type="text/css">
	.question {
		border: 1px black solid;
		padding: 1em;
		margin: 1em;
	}
	.question .title {
		clear: both;
		font-weight: bold;
		display: block;
	}
</style>
<?php

	}
/*
 Use this SQL to find the courses with these questions in them:
 
 select distinct c.id, c.shortname, c.fullname from 
mdl_course c
join mdl_quiz qz
on qz.course = c.id
join mdl_quiz_question_instances qqi
on qqi.quiz = qz.id
join
mdl_question q
on qqi.question = q.id
where questiontext like '%accents.js%';

Use this SQL to restore the questions from the backup:

update mdl_question q, mdl_question_jsfixbackup js
set q.questiontext = js.questiontext 
where q.id = js.id

*/

require_once '../config.php';

// Map course ids to language
$languages = array(
	2654 => 'es',
	2767 => 'it',
	2829 => 'es',
	2853 => 'es',
	5455 => 'it',
	5468 => 'it',
	5698 => 'it',
	5699 => 'it',
	5700 => 'it',
	5704 => 'it',
	5705 => 'es',
	5706 => 'es',
	5707 => 'es',
	5710 => 'es',
	8498 => 'it',
	8503 => 'es',
	16755 => 'it'
);

// Backup existing values
if(!$test) {
	if(! $feedback = execute_sql("CREATE TABLE mdl_question_jsfixbackup AS SELECT * FROM mdl_question")) {
		die("Unable to backup data");
	}
}

$questions = get_records_sql("select * from mdl_question where questiontext like '%accents.js%'");

foreach($questions as $question) {
	if($result = remove_accentsjs($question->id)){
		if($test) {
			echo "<div class='question'><p class='title'>Question " . $question->id . "</p>" . $result . "</div>";
		} else {
			echo "Question " . $question->id . ": " . $result . "\n";
		}
	}
	flush();
}

function remove_accentsjs($questionid) {
	global $languages, $test;
	
	if(! $r = get_record('question', 'id', $questionid) ){
		return false;
	}
	
	$lang = '';
	if($instances = get_records_sql("select distinct c.id from 
		mdl_course c
		join mdl_quiz qz
		on qz.course = c.id
		join mdl_quiz_question_instances qqi
		on qqi.quiz = qz.id
		where qqi.question = $questionid")) {
		
		foreach($instances as $instance) {
			if(array_key_exists($instance->id, $languages)) {
				$lang = $languages[$instance->id];
				break;
			}
		}
	}
	
	$dom = new DOMDocument;
	libxml_use_internal_errors(true);
	$dom->loadHTML('<?xml encoding="UTF-8">' . $r->questiontext);
	libxml_clear_errors();
	
	$result = '';

	foreach($dom->getElementsByTagName('body') as $body) {
		$out = new DOMDocument();
		$imported_body = $out->importNode($body, true);
		$out->appendChild($imported_body);
		
		// Create keywords node
		if(!empty($lang)) {
			$lang = " !$lang ";
		}
		$accentsNode = $out->createTextNode("{strath:accents$lang}");
		
		foreach($out->getElementsByTagName('table') as $child) {
			if(strpos($child->nodeValue, 'James McMahon') >= 0 && $child->hasAttribute('border') && $child->getAttribute('border') == '4') {
				$child->parentNode->replaceChild($accentsNode, $child);
			}
		}
		foreach($out->getElementsByTagName('script') as $child) {
			$child->parentNode->removeChild($child);
		}
		$out->normalize();
		$html = $out->saveHTML();
		$result .= preg_replace('/(^<body\>|<\/body>$)/', '', $html);
	}
	
	if($test) {
		return $result;
	} else {
		$update = new stdClass();
		$update->id = $questionid;
		$update->questiontext = $result;
		
		return update_record('question', $update);
	}
	
}