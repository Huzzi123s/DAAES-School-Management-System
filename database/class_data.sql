
INSERT INTO classes (class_name)
SELECT 'Nursery' WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'Nursery')
UNION ALL
SELECT 'Prep' WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'Prep')
UNION ALL
SELECT 'Class 1' WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'Class 1')
UNION ALL
SELECT 'Class 2' WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'Class 2')
UNION ALL
SELECT 'Class 3' WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'Class 3')
UNION ALL
SELECT 'Class 4' WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'Class 4')
UNION ALL
SELECT 'Class 5' WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'Class 5')
UNION ALL
SELECT 'Class 6' WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'Class 6')
UNION ALL
SELECT 'Class 7' WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'Class 7')
UNION ALL
SELECT 'Class 8' WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'Class 8')
UNION ALL
SELECT 'Class 9' WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'Class 9')
UNION ALL
SELECT 'Class 10 (Matric)' WHERE NOT EXISTS (SELECT 1 FROM classes WHERE class_name = 'Class 10 (Matric)');

INSERT INTO sections (class_id, section_name)
SELECT c.id, s.section_name
FROM classes c
CROSS JOIN (
    SELECT 'A' AS section_name
    UNION ALL
    SELECT 'B'
) s
WHERE NOT EXISTS (
    SELECT 1 FROM sections
    WHERE sections.class_id = c.id
    AND sections.section_name = s.section_name
);
