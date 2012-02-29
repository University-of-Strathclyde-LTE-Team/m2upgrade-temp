<?php

require_once '../config.php';

/*
 * update mdl_question set questiontext = replace(questiontext, 'SHORTANSWER', 'SHORTANSWER:') where questiontext like '%:SHORTANSWER%' and questiontext not like '%SHORTANSWER:%';
 */

/* if($questions = get_records_sql("select * from mdl_question where questiontext like '%~}'")){

	foreach($questions as $question) {
		$questiontext = preg_replace('|~}$|', '}', $question->questiontext);
		echo "{$question->questiontext}    $questiontext\n";
		//set_field('question', 'questiontext', $questiontext);
	}

} */

if($questions = get_recordset_sql("select * from mdl_question where questiontext like '%SHORTANSWER%' and questiontext not like '%SHORTANSWER:%'")){

	while($question = rs_fetch_next_record($questions)) {
		$questiontext = str_replace('SHORTANSWER', 'SHORTANSWER:', $question->questiontext);
		echo "{$question->questiontext}    $questiontext\n";
		set_field('question', 'questiontext', addslashes($questiontext), 'id', $question->id);
	}

}