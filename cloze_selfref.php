<?php

/*
 * Fix cloze questions which are self-referential (i.e. in mdl_question_multianswer
 * the sequence contains the id of the row)
 */

require_once '../config.php';

echo get_string('questionsinuse', 'quiz'); die();

$borked_questions = get_records_sql("SELECT q.* FROM mdl_question q JOIN mdl_question_multianswer qma ON q.id = qma.question 
WHERE sequence like concat('%', question, '%')
AND parent = 0");

foreach($borked_questions as $question) {
	//fix_cloze_selfref($question);
}

$question = get_record('question', 'id', 233528);
fix_cloze_selfref($question);

function fix_cloze_selfref($question) {
	
	
	if($question->qtype != 'multianswer') {
		throw new Exception("fix_cloze_selfref only supports multianswer questions", 1);
	}
	
	if(! $multianswer = get_record('question_multianswer', 'question', $question->id) ) {
		throw new Exception("Can't find multianswer question record", 2);
	}
	// Create a new child short answer question (with a single answer)
	$new_question = new stdClass();
	$new_question->category = $question->category;
	$new_question->parent = $question->id;
	$new_question->name = $question->name;
	$new_question->qtype = 'shortanswer';
	$new_question->questiontext = '{1:SHORTANSWER:=broken}';
	$new_question->generalfeedback = '';
	
	$id = insert_record('question', $new_question);
	
	$answer = new stdClass();
	$answer->question = $id;
	$answer->answer = '*';
	$answer->fraction = 0;
	$answer->feedback = 'This question should not be in use. Please contact your lecturer.';
	
	$answerid = insert_record('question_answers', $answer);
	
	$shortanswer = new stdClass();
	$shortanswer->question = $id;
	$shortanswer->answers = $answerid;
	
	$shortanswerid = insert_record('shortanswer', $shortanswer);
	
	// Change the sequence in mdl_question_multianswer to point all blanks at new question
	$update = new stdClass();
	$update->id = $multianswer->id;
	$update->sequence = str_replace($question->id, $shortanswerid, $multianswer->sequence);
	
	return update_record('question_multianswer', $update);
	
}