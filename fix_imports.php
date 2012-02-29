<?php

require_once '../config.php';
require_once $CFG->dirroot.'/lib/questionlib.php';
require_once $CFG->dirroot.'/question/type/multianswer/db/upgrade.php';

// Fix the categories of subquestions
question_multianswer_fix_subquestion_parents_and_categories();

// Fix questions (WebCT imports) which have no stamp or version
$questions = get_recordset('question');
while($question = rs_fetch_next_record($questions)) {
	if(empty($question->stamp)) {
		echo "{$question->id}\n";
		set_field('question', 'stamp', question_hash($question), 'id', $question->id);
	}
	if(empty($question->version)) {
		set_field('question', 'version', question_hash($question), 'id', $question->id);
	}
}

// "Fix" questions with self-referential child-parent relationships
$result = execute_sql("UPDATE mdl_question_multianswer ma, mdl_question q
SET sequence = '' 
WHERE ma.question = q.id
AND sequence like concat('%', question, '%')
AND parent = 0", true);