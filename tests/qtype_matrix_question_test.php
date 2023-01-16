<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the matrix question definition class.
 */

global $CFG;
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/question/engine/tests/helpers.php');

/**
 * Unit tests for the true-false question definition class.
 *
 */
class qtype_matrix_question_test extends advanced_testcase {

    public function test_is_complete_response(): void {
        $question = self::make_question('multiple');

        $answer = [];
        $this->assertTrue($question->is_complete_response($answer));

        $answer = self::make_answer_correct($question);
        $this->assertTrue($question->is_complete_response($answer));

        $answer = self::make_answer_incorrect($question);
        $this->assertTrue($question->is_complete_response($answer));

        $question = self::make_question('single');
        $answer = [];
        $this->assertFalse($question->is_complete_response($answer));
        $message = $question->get_validation_error($answer);
        $this->assertNotEmpty($message);

        $answer = self::make_answer_correct($question);
        $this->assertTrue($question->is_complete_response($answer));

        $answer = self::make_answer_incorrect($question);
        $this->assertTrue($question->is_complete_response($answer));
    }

    /**
     *
     * @param string $type
     * @return question_definition the requested question object.
     */
    protected static function make_question(string $type = 'kprime'): question_definition {
        return test_question_maker::make_question('matrix', $type);
    }

    /**
     *
     * @param qtype_matrix_question $question
     * @return array
     */
    protected static function make_answer_correct($question): array {
        $result = [];
        foreach ($question->rows as $row) {
            $col = 0;
            $key = $question->key($row, $col);
            $result[$key] = $question->multiple ? 'on' : $col;
        }

        return $result;
    }

    /**
     *
     * @param qtype_matrix_question $question
     * @return array
     */
    protected static function make_answer_incorrect($question): array {
        $result = [];
        foreach ($question->rows as $row) {
            $col = 3;
            $key = $question->key($row, $col);
            $result[$key] = $question->multiple ? 'on' : $col;
        }

        return $result;
    }

    public function test_get_correct_response(): void {
        $question = self::make_question('multiple');

        $answer = self::make_answer_correct($question);
        $correct = $question->get_correct_response();
        $this->assertEquals($answer, $correct);

        $answer = self::make_answer_incorrect($question);
        $this->assertNotEquals($answer, $question->get_correct_response());

        $question = self::make_question('single');

        $answer = self::make_answer_correct($question);
        $this->assertEquals($answer, $question->get_correct_response());

        $answer = self::make_answer_incorrect($question);
        $this->assertNotEquals($answer, $question->get_correct_response());
    }

    public function test_get_question_summary(): void {
        $question = self::make_question('multiple');
        $summary = $question->get_question_summary();
        $this->assertNotEmpty($summary);
    }

    public function test_summarise_response(): void {
        $question = self::make_question('multiple');

        $answer = self::make_answer_correct($question);
        $summary = $question->summarise_response($answer);
        $this->assertNotEmpty($summary);

        $answer = self::make_answer_incorrect($question);
        $summary = $question->summarise_response($answer);
        $this->assertNotEmpty($summary);

        $question = self::make_question('single');
        $summary = $question->get_question_summary();
        $this->assertNotEmpty($summary);

        $answer = self::make_answer_correct($question);
        $summary = $question->summarise_response($answer);
        $this->assertNotEmpty($summary);

        $answer = self::make_answer_incorrect($question);
        $summary = $question->summarise_response($answer);
        $this->assertNotEmpty($summary);
    }

    public function test_is_same_response(): void {
        $question = self::make_question('multiple');

        $correct = $question->get_correct_response();
        $answer = self::make_answer_correct($question);

        $this->assertEquals($correct, $answer);
        $this->assertEquals($correct, $correct);

        $answer = self::make_answer_incorrect($question);
        $this->assertEquals($answer, $answer);
        $this->assertNotEquals($answer, $correct);
    }

    public function test_grading(): void {
        $question = self::make_question('all');
        $question->multiple = true;
        $this->question_grading_pass($question, 0.5);

        $answer = self::make_answer_multiple_partial($question);
        $grade = $question->grade_response($answer);
        $this->assertEquals([0, question_state::$gradedwrong], $grade);

        $question->multiple = false;
        $this->question_grading_pass($question, 0.5);

        $question = self::make_question('kprime');
        $question->multiple = true;
        $this->question_grading_pass($question, 0);

        $answer = self::make_answer_multiple_partial($question);
        $grade = $question->grade_response($answer);

        $this->assertEquals([0, question_state::$gradedwrong], $grade);

        $question->multiple = false;
        $this->question_grading_pass($question, 0);
    }

    protected function question_grading_pass($question, float $partialgrading = 0.5): void {
        $answer = self::make_answer_correct($question);
        $grade = $question->grade_response($answer);
        $this->assertEquals([1, question_state::$gradedright], $grade);

        $answer = self::make_answer_incorrect($question);
        $grade = $question->grade_response($answer);
        $this->assertEquals([0, question_state::$gradedwrong], $grade);

        $answer = self::make_answer_partial($question);
        $grade = $question->grade_response($answer);
        if ($partialgrading == 0) {
            $state = question_state::$gradedwrong;
        } else if ($partialgrading == 1) {
            $state = question_state::$gradedright;
        } else {
            $state = question_state::$gradedpartial;
        }
        $this->assertEquals([$partialgrading, $state], $grade);
    }

    /**
     *
     * @param qtype_matrix_question $question
     * @return array
     */
    protected static function make_answer_partial($question): array {
        $result = [];
        foreach ($question->rows as $row) {
            $col = $row->id < 2 ? 0 : 3;
            $key = $question->key($row, $col);
            $result[$key] = $question->multiple ? 'on' : $col;
        }

        return $result;
    }

    /**
     *
     * @param qtype_matrix_question $question
     * @return array
     */
    protected static function make_answer_multiple_partial($question): array {
        $result = [];
        foreach ($question->rows as $row) {
            if ($row->id < 2) {
                // All correct.
                $key = $question->key($row, $col = 0);
                $result[$key] = 'on';
                $key = $question->key($row, $col = 1);
                $result[$key] = 'on';
            } else if ($row->id == 2) {
                // One correct one wrong.
                $key = $question->key($row, $col = 1);
                $result[$key] = 'on';
                $key = $question->key($row, $col = 2);
                $result[$key] = 'on';
            } else {
                // All wrong.
                $key = $question->key($row, $col = 2);
                $result[$key] = 'on';
                $key = $question->key($row, $col = 3);
                $result[$key] = 'on';
            }
        }

        return $result;
    }

    /**
     *
     * @param qtype_matrix_question $question
     * @return array
     */
    protected static function make_answer_multiple_correct($question): array {
        $result = [];
        foreach ($question->rows as $row) {
            $key = $question->key($row, $col = 0);
            $result[$key] = 'on';
            $key = $question->key($row, $col = 1);
            $result[$key] = 'on';
        }

        return $result;
    }

    /**
     *
     * @param qtype_matrix_question $question
     * @return array
     */
    protected static function make_answer_multiple_incorrect($question): array {
        $result = [];
        foreach ($question->rows as $row) {
            $key = $question->key($row, $col = 2);
            $result[$key] = 'on';
            $key = $question->key($row, $col = 3);
            $result[$key] = 'on';
        }

        return $result;
    }

}
