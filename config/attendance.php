<?php
function attendanceCsrfToken(){if(empty($_SESSION['attendance_csrf']))$_SESSION['attendance_csrf']=bin2hex(random_bytes(32));return $_SESSION['attendance_csrf'];}
function verifyAttendanceCsrf(){return isset($_POST['csrf_token'],$_SESSION['attendance_csrf'])&&hash_equals($_SESSION['attendance_csrf'],$_POST['csrf_token']);}
function attendancePeriodLocked($conn,$date){$period=substr($date,0,7);$s=$conn->prepare('SELECT is_locked FROM attendance_period_locks WHERE period_month=? LIMIT 1');$s->bind_param('s',$period);$s->execute();$r=$s->get_result()->fetch_assoc();$s->close();return !empty($r['is_locked']);}
function attendancePolicy($conn){$defaults=['grace_minutes'=>10,'half_day_minutes'=>240,'full_day_minutes'=>480,'overtime_after_minutes'=>480,'allow_early_check_in_minutes'=>120];$r=$conn->query('SELECT * FROM attendance_policy WHERE id=1');return $r&&$r->num_rows?array_merge($defaults,$r->fetch_assoc()):$defaults;}
function shiftWindow($date,$start,$end){$startAt=new DateTime("$date $start");$endAt=new DateTime("$date $end");if($endAt<=$startAt)$endAt->modify('+1 day');return[$startAt,$endAt];}
function attendanceMetrics(DateTime $in,DateTime $out,DateTime $shiftStart,DateTime $shiftEnd,array $p){
    $worked=max(0,(int)floor(($out->getTimestamp()-$in->getTimestamp())/60));
    $late=max(0,(int)floor(($in->getTimestamp()-$shiftStart->getTimestamp())/60)-(int)$p['grace_minutes']);
    $early=max(0,(int)floor(($shiftEnd->getTimestamp()-$out->getTimestamp())/60));
    $overtime=max(0,$worked-(int)$p['overtime_after_minutes']);
    if($worked<(int)$p['half_day_minutes'])$status='Absent';elseif($worked<(int)$p['full_day_minutes'])$status='Half Day';elseif($late>0)$status='Late';elseif($early>0)$status='Early Out';else$status='Present';
    return compact('worked','late','early','overtime','status');
}
