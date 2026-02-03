<!--
Sync Impact Report
- Version: (none) → 1.0.0
- Modified Principles: N/A (initial version)
- Added Sections: Core Principles, Technical Constraints, Governance
- Removed Sections: N/A
- Templates Requiring Updates: .specify/templates/plan-template.md (⚠ none present), .specify/templates/spec-template.md (⚠ none present), .specify/templates/tasks-template.md (⚠ none present)
- Follow-up TODOs: None
-->

# Project Constitution: TimeDryClean

**Constitution Version**: 1.0.0  
**Ratification Date**: 2026-02-01  
**Last Amended Date**: 2026-02-01

---

## 1. Core Principles

### 1.1 Clean Code Discipline (MUST)
- Code MUST follow Laravel conventions and PSR-12 style guidelines.
- Each change MUST keep controllers thin, business logic in services, and views concise.
- Dead code, commented-out blocks, and magic numbers MUST be removed or replaced with named constants.
- All new code MUST include inline documentation only when the intent is not obvious from naming.

### 1.2 Simple UX (MUST)
- User flows MUST minimize clicks: completing primary actions requires no more than three interactions.
- Interface text MUST be clear, concise, and free of jargon.
- Visual hierarchy MUST highlight the primary call to action on every screen.
- Error states MUST provide actionable guidance without exposing internal details.

### 1.3 Responsive Design (MUST)
- Layouts MUST adapt gracefully from 320px mobile screens to large desktop displays.
- Interactive elements MUST respect touch targets (minimum 44px) and keyboard accessibility.
- CSS breakpoints MUST leverage Bootstrap’s grid system and utility classes.
- Animations MUST preserve 60fps performance and degrade gracefully when `prefers-reduced-motion` is enabled.

### 1.4 Minimal Dependencies (MUST)
- Only the libraries listed in package.json (Laravel backend, Bootstrap, jQuery, FontAwesome) MAY be used.
- Introducing new third-party libraries is PROHIBITED unless constitution is amended first.
- Custom functionality MUST be implemented with vanilla PHP/JavaScript when feasible.
- Build tooling MUST remain limited to the current Vite configuration.

### 1.5 No Automated Testing (MUST)
- No unit, integration, or end-to-end tests MAY be authored or executed.
- Manual QA checklists MUST replace automated testing and be attached to each feature delivery.
- CI pipelines MUST exclude any automated test stages.

### 1.6 Approved Technology Stack (MUST)
- Backend MUST remain Laravel as already defined in composer.json.
- Frontend styling and interaction MUST use Bootstrap and jQuery respectively.
- Icons MUST come exclusively from FontAwesome as bundled.
- Any deviation requires a major constitution amendment.

---

## 2. Technical Constraints

- PHP version MUST align with the project’s current runtime (≥ 8.1).
- Database schema changes MUST be implemented via Laravel migrations with reversible `down` methods, even without automated tests.
- CSS and JavaScript assets MUST compile through Vite without adding new build steps.
- Accessibility conformance MUST meet WCAG 2.1 AA.
- Performance budgets: <2s initial load on broadband, <200ms interaction latency, 60fps animations.

---

## 3. Governance & Compliance

### 3.1 Amendment Process
1. Propose change via `/speckit.constitution` command with rationale.
2. Increment version number following semantic versioning rules (major/minor/patch).
3. Update Sync Impact Report section detailing affected artifacts.
4. Obtain explicit approval from project owner before ratification.

### 3.2 Compliance Reviews
- Every feature delivery MUST include a manual QA checklist verifying adherence to core principles.
- Constitution compliance MUST be validated before `/speckit.plan` is considered complete.
- Violations require immediate remediation or formal amendment before further development.

### 3.3 Versioning Policy
- **MAJOR**: Any change that relaxes or removes MUST principles.
- **MINOR**: Addition of new principles or significant constraints.
- **PATCH**: Clarifications, formatting, or non-substantive updates.

### 3.4 Enforcement
- Contributors MUST acknowledge this constitution before contributing.
- Pull requests MUST reference relevant principles in descriptions.
- Deviations without amendment constitute a blocker and MUST be resolved prior to merge.
