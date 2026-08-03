ALTER TABLE salary_slips
    ADD UNIQUE KEY IF NOT EXISTS uq_salary_slip_employee_month (employee_id, salary_month),
    ADD KEY IF NOT EXISTS idx_salary_slips_employee (employee_id);

ALTER TABLE salary_structure_components
    ADD UNIQUE KEY IF NOT EXISTS uq_salary_structure_component (salary_structure_id, component_id),
    ADD KEY IF NOT EXISTS idx_salary_component (component_id);

