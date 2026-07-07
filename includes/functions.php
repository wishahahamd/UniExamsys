<?php
// includes/functions.php

function getGradingScale($conn) {
    static $scale = null;
    if ($scale === null) {
        $scale = [];
        $res = $conn->query("SELECT * FROM grading_scale ORDER BY min_percentage DESC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $scale[] = $row;
            }
        }
    }
    return $scale;
}

function calculateGrade($total_marks, $is_ufm, $scale = []) {
    if($is_ufm) return ['grade' => 'UFM', 'point' => 0.00];
    if($total_marks === null || $total_marks === "NULL") return ['grade' => 'X', 'point' => 0.00];
    
    $marks = (float)$total_marks;
    foreach ($scale as $s) {
        if ($marks >= (float)$s['min_percentage'] && $marks <= (float)$s['max_percentage']) {
            return ['grade' => $s['grade'], 'point' => (float)$s['grade_point']];
        }
    }
    
    // Fallback default scale in case database list was empty
    if($marks >= 90) return ['grade' => 'A+', 'point' => 4.00];
    if($marks >= 85) return ['grade' => 'A', 'point' => 3.67];
    if($marks >= 80) return ['grade' => 'A-', 'point' => 3.33];
    if($marks >= 75) return ['grade' => 'B+', 'point' => 3.00];
    if($marks >= 70) return ['grade' => 'B', 'point' => 2.67];
    if($marks >= 65) return ['grade' => 'B-', 'point' => 2.33];
    if($marks >= 60) return ['grade' => 'C+', 'point' => 2.00];
    if($marks >= 55) return ['grade' => 'C', 'point' => 1.67];
    if($marks >= 50) return ['grade' => 'C-', 'point' => 1.33];
    return ['grade' => 'F', 'point' => 0.00];
}

function processExamResults($exam_id, $conn) {
    $scale = getGradingScale($conn);
    
    // Fetch all marks for this exam
    $stmt = $conn->prepare("SELECT mark_id, total_marks, is_ufm FROM marks WHERE exam_id = ?");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $conn->begin_transaction();
    try {
        $update = $conn->prepare("UPDATE marks SET grade = ?, grade_point = ? WHERE mark_id = ?");
        while($row = $res->fetch_assoc()) {
            $calc = calculateGrade($row['total_marks'], $row['is_ufm'], $scale);
            $update->bind_param("sdi", $calc['grade'], $calc['point'], $row['mark_id']);
            $update->execute();
        }
        
        $conn->query("UPDATE exams SET is_published = TRUE WHERE exam_id = $exam_id");
        $conn->commit();
        return true;
    } catch(Exception $e) {
        $conn->rollback();
        return false;
    }
}

function getStudentAcademicSummary($student_id, $conn) {
    $query = "
        SELECT c.credits, m.grade, m.grade_point, m.is_ufm
        FROM marks m
        JOIN exams e ON m.exam_id = e.exam_id
        JOIN courses c ON m.course_id = c.course_id
        WHERE m.student_id = ? AND e.is_published = TRUE
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $total_credits = 0;
    $total_points = 0;
    $grades = [];
    
    while ($row = $res->fetch_assoc()) {
        $credits = (int)$row['credits'];
        $gp = $row['grade_point'];
        $grade = $row['grade'];
        
        if ($row['is_ufm']) {
            $grade = 'UFM';
            $gp = 0.00;
        }
        
        $total_credits += $credits;
        if ($gp !== null) {
            $total_points += ($credits * $gp);
        }
        
        if ($grade) {
            $grades[$grade] = ($grades[$grade] ?? 0) + 1;
        }
    }
    
    $cgpa = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0.00;
    
    return [
        'cgpa' => number_format($cgpa, 2),
        'total_credits' => $total_credits,
        'grade_counts' => $grades
    ];
}
?>
