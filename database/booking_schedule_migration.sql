-- Add safe sample availability to an existing FitZone database.
-- This migration is additive and can be run more than once.

CREATE OR REPLACE VIEW v_available_slots AS
SELECT s.schedule_id, s.work_date, s.start_time, s.end_time, s.status,
       t.trainer_id, t.full_name AS trainer_name, t.specialty
FROM trainer_schedule s
JOIN personal_trainer t ON t.trainer_id = s.trainer_id
WHERE t.is_active = 1
  AND s.status = 'available'
  AND s.work_date >= CURRENT_DATE()
  AND NOT EXISTS (
      SELECT 1
      FROM booking b
      JOIN trainer_schedule occupied ON occupied.schedule_id = b.schedule_id
      WHERE b.status IN ('pending', 'confirmed')
        AND occupied.trainer_id = s.trainer_id
        AND occupied.work_date = s.work_date
        AND occupied.start_time < s.end_time
        AND occupied.end_time > s.start_time
  );

INSERT INTO trainer_schedule
    (trainer_id, work_date, start_time, end_time, status)
SELECT candidate.trainer_id,
       candidate.work_date,
       candidate.start_time,
       candidate.end_time,
       'available'
FROM (
    SELECT 1 AS trainer_id,
           DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY) AS work_date,
           '07:00:00' AS start_time,
           '08:00:00' AS end_time
    UNION ALL
    SELECT 2, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), '09:00:00', '10:00:00'
    UNION ALL
    SELECT 3, DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), '15:00:00', '16:00:00'
    UNION ALL
    SELECT 4, DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), '16:00:00', '17:00:00'
) AS candidate
WHERE NOT EXISTS (
    SELECT 1
    FROM trainer_schedule existing
    WHERE existing.trainer_id = candidate.trainer_id
      AND existing.work_date = candidate.work_date
      AND existing.start_time = candidate.start_time
);
