-- Viva demonstration script. Run against crime_investigation after install.
USE crime_investigation;

-- CREATE / ALTER / DROP on a safe object (not a production entity)
CREATE TABLE IF NOT EXISTS tmp_demo (
  demo_id INT PRIMARY KEY AUTO_INCREMENT,
  note VARCHAR(80) NOT NULL
);
ALTER TABLE tmp_demo ADD COLUMN created_on DATE NULL;
DROP TABLE tmp_demo;

-- DISTINCT
SELECT DISTINCT department FROM officer;
SELECT DISTINCT city FROM location;
SELECT DISTINCT status FROM `case`;

-- WHERE / LIKE / IN / BETWEEN / ORDER BY
SELECT case_id, title, status
FROM `case`
WHERE status IN ('Open', 'Under Investigation')
  AND date_opened BETWEEN '2026-01-01' AND '2026-12-31'
  AND title LIKE '%theft%'
ORDER BY date_opened DESC;

-- UPDATE / DELETE samples (commented to protect demo data)
-- UPDATE `case` SET status = 'Closed' WHERE case_id = 6;
-- DELETE FROM evidence WHERE evidence_id = 999;

-- INNER JOIN
SELECT c.title, o.name AS officer, l.city
FROM `case` c
INNER JOIN officer o ON o.officer_id = c.officer_id
INNER JOIN location l ON l.location_id = c.location_id;

-- Aggregates
SELECT COUNT(*) AS cases, MIN(date_opened) AS earliest, MAX(date_opened) AS latest
FROM `case`;
SELECT AVG(age) AS avg_suspect_age FROM suspect;

-- Subquery: officers handling more than the average number of cases
SELECT o.name, COUNT(c.case_id) AS total
FROM officer o
JOIN `case` c ON c.officer_id = o.officer_id
GROUP BY o.officer_id, o.name
HAVING COUNT(c.case_id) > (
  SELECT AVG(cnt) FROM (
    SELECT COUNT(*) AS cnt FROM `case` WHERE officer_id IS NOT NULL GROUP BY officer_id
  ) AS per_officer
);
